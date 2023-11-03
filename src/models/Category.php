<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\CategoryActiveForm;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\db\NestedTreeTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;

/**
 * Category is the base model class for all CMS categories, which can be linked to multiple {@link Entry} records by
 * {@link EntryCategory} relations.
 *
 * @property int $parent_id
 * @property int $lft
 * @property int $rgt
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $content
 * @property int $entry_count
 *
 * @property-read Section[] $sections
 * @property-read Asset[] $assets
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read EntryCategory $entryCategory {@see static::getEntryCategory()}
 * @property-read EntryCategory[] $entryCategories {@see static::getEntryCategories()}
 * @property-read static $parent {@see static::getParent()}
 * @property-read static[] $ancestors {@see static::getDescendants()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 */
class Category extends ActiveRecord
{
    use NestedTreeTrait;

    /**
     * Cache.
     */
    public const CATEGORIES_CACHE_KEY = 'get-categories-cache';

    public string|false $contentType = false;
    public array|string|null $slugTargetAttribute = ['parent_id', 'slug'];

    /**
     * @see Category::getCategories()
     */
    protected static ?array $_categories = null;

    public function behaviors(): array
    {
        return [...parent::behaviors(), 'RedirectBehavior' => RedirectBehavior::class];
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
                [
                    ['parent_id'],
                    'number',
                    'integerOnly' => true,
                ],
                [
                    ['parent_id'],
                    $this->validateParentId(...),
                    'skipOnEmpty' => false,
                ],
                [
                    ['name'],
                    'required',
                ],
                [
                    ['slug'],
                    'required',
                    'when' => fn(): bool => $this->isSlugRequired()
                ],
                [
                    ['name', 'slug', 'title', 'description', 'content'],
                    'trim',
                ],
                [
                    ['name', 'title', 'description'],
                    'string',
                    'max' => 250,
                ],
                [
                    ['slug'],
                    'string',
                    'max' => static::SLUG_MAX_LENGTH,
                ],
                [
                    ['slug'],
                    $this->slugUniqueValidator,
                    'targetAttribute' => $this->slugTargetAttribute,
                ],
            ])
        ];
    }

    public function beforeValidate(): bool
    {
        $this->ensureSlug();

        if (!static::getModule()->enableNestedCategories) {
            $this->parent_id = null;
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert): bool
    {
        if (!$this->slug) {
            $this->slug = null;
        }

        $this->updateTreeBeforeSave();
        return parent::beforeSave($insert);
    }

    /**
     * On parent id change all related entries (linked to this category as well as to the child categories)
     * need to be added to the new parent categories, if {@link \davidhirtz\yii2\cms\Module::$inheritNestedCategories}
     * is true. Previous parent {@link EntryCategory} relations will not be deleted.
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            if ($this->parent_id && array_key_exists('parent_id', $changedAttributes)) {
                $this->insertEntryCategoryAncestors();
            }
        }

        static::invalidateCategoriesCache();

        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeDelete(): bool
    {
        if ($isValid = parent::beforeDelete()) {
            $this->deleteNestedTreeItems();

            if ($this->entry_count) {
                $this->deleteEntryCategories();
            }
        }

        return $isValid;
    }

    public function afterDelete(): void
    {
        $this->updateNestedTreeAfterDelete();
        parent::afterDelete();
    }

    public function getEntryCategory(): ActiveQuery
    {
        return $this->hasOne(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    public function getEntries(): EntryQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryCategories');
    }

    public function getEntryCategories(): ActiveQuery
    {
        return $this->hasMany(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    public function recalculateEntryCount(): static
    {
        $this->entry_count = (int)$this->getEntryCategories()->count();
        return $this;
    }

    /**
     * Inserts related {@link EntryCategory} records to this records ancestor categories. This method is called after
     * the parent id was changed and can insert quite a lot of records. This might need to be overridden on applications
     * with MANY entry-category relations.
     */
    protected function insertEntryCategoryAncestors(): void
    {
        // If the category doesn't have `inheritNestedCategories` enabled, descendant categories need to be used.
        $categoryIds = $this->inheritNestedCategories() ? $this->id : array_keys(array_filter($this->descendants, fn(self $category): bool => $category->inheritNestedCategories()));

        if ($categoryIds) {
            $entries = Entry::find()
                ->innerJoinWith([
                    'entryCategory' => function (ActiveQuery $query) use ($categoryIds) {
                        $query->onCondition([EntryCategory::tableName() . '.[[category_id]]' => $categoryIds]);
                    }
                ])
                ->all();

            if ($entries) {
                // Refresh category ancestors once to prevent duplicate queries.
                $this->getAncestors(true);

                foreach ($entries as $entry) {
                    $entryCategory = $entry->entryCategory;

                    $entryCategory->populateCategoryRelation($this);
                    $entryCategory->insertCategoryAncestors();
                    $entryCategory->updateEntryCategoryIds();
                }
            }
        }
    }

    /**
     * Deletes all related entry categories before the {@link EntryCategory} records would be deleted by the database's
     * foreign key relation. This enables recalculating the related record as well as adding {@link Trail} records.
     * This might need to be overridden on applications with MANY entry-category relations.
     */
    protected function deleteEntryCategories(): void
    {
        $entryCategories = $this->getEntryCategories()
            ->with('entry')
            ->all();

        foreach ($entryCategories as $entryCategory) {
            $entryCategory->delete();
        }
    }

    public static function find(): CategoryQuery
    {
        return Yii::createObject(CategoryQuery::class, [static::class]);
    }

    public function findSiblings(): CategoryQuery
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    public function getSitemapQuery(): CategoryQuery
    {
        return static::find()
            ->selectSitemapAttributes()
            ->orderBy(['id' => SORT_ASC]);
    }

    /**
     * @return static[]
     */
    public static function findCategories(): array
    {
        return static::find()
            ->selectSiteAttributes()
            ->whereStatus()
            ->indexBy('id')
            ->all();
    }

    /**
     * @return static[]
     */
    public static function getCategories(): array
    {
        if (static::$_categories === null) {
            $dependency = new TagDependency(['tags' => static::CATEGORIES_CACHE_KEY]);
            static::$_categories = static::getModule()->categoryCachedQueryDuration > 0 ? static::getDb()->cache(static::findCategories(...), static::getModule()->categoryCachedQueryDuration, $dependency) : static::findCategories();
        }

        return static::$_categories;
    }

    public static function invalidateCategoriesCache(): void
    {
        if (static::getModule()->categoryCachedQueryDuration > 0) {
            TagDependency::invalidate(Yii::$app->getCache(), static::CATEGORIES_CACHE_KEY);
        }
    }

    public function updateEntryOrder(array $entryIds): void
    {
        $entries = $this->getEntryCategories()
            ->andWhere(['entry_id' => $entryIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        if (EntryCategory::updatePosition($entries, array_flip($entryIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Entry order changed'));
        }
    }

    public static function getBySlug(string $slug, int $parentId = null): ?static
    {
        if ($slug) {
            if (strpos($slug, '/')) {
                $category = $prevParentId = null;

                foreach (explode('/', $slug) as $part) {
                    if ($category = static::getBySlug($part, $prevParentId)) {
                        $prevParentId = $category->id;
                    }
                }

                return $category;
            }

            foreach (static::getCategories() as $category) {
                if ($category->getI18nAttribute('slug') == $slug && ($category->parent_id == $parentId)) {
                    return $category;
                }
            }
        }

        return null;
    }

    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'lft',
            'rgt',
            'entry_count',
        ]);
    }

    public function getTrailModelName(): string
    {
        if ($this->id) {
            return $this->getI18nAttribute('name') ?: Yii::t('skeleton', '{model} #{id}', [
                'model' => $this->getTrailModelType(),
                'id' => $this->id,
            ]);
        }

        return $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'Category');
    }

    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/category/update', 'id' => $this->id] : false;
    }

    public function getRoute(): false|array
    {
        return array_filter(['/cms/site/index', 'category' => $this->getNestedSlug()]);
    }

    public function getNestedSlug(): string
    {
        $slugs = [];

        foreach ($this->ancestors as $ancestor) {
            $slugs[] = $ancestor->getI18nAttribute('slug');
        }

        return implode('/', [...$slugs, $this->getI18nAttribute('slug')]);
    }

    public function getEntryOrderBy(): bool|array
    {
        return [EntryCategory::tableName() . '.[[position]]' => SORT_ASC];
    }

    /**
     * @return class-string
     */
    public function getActiveForm(): string
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? CategoryActiveForm::class;
    }

    public function hasEntriesEnabled(): bool
    {
        return true;
    }

    public function inheritNestedCategories(): bool
    {
        return static::getModule()->inheritNestedCategories;
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'name' => Yii::t('cms', 'Name'),
            'parent_id' => Yii::t('cms', 'Parent category'),
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'branchCount' => Yii::t('cms', 'Subcategories'),
            'entry_count' => Yii::t('cms', 'Entries')
        ];
    }

    public function formName(): string
    {
        return 'Category';
    }

    public static function tableName(): string
    {
        return static::getModule()->getTableName('category');
    }
}