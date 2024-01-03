<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\traits\SlugAttributeTrait;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\traits\NestedTreeTrait;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rgt
 * @property int $position
 * @property string $name
 * @property string|null $slug
 * @property string|null $title
 * @property string|null $description
 * @property string|null $content
 * @property int $entry_count
 *
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read EntryCategory|null $entryCategory {@see static::getEntryCategory()}
 * @property-read EntryCategory[] $entryCategories {@see static::getEntryCategories()}
 * @property-read static|null $parent {@see static::getParent()}
 * @property-read static[] $ancestors {@see static::getAncestors()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 */
class Category extends ActiveRecord
{
    use NestedTreeTrait;
    use SlugAttributeTrait;

    public string|false $contentType = false;
    public array|string|null $slugTargetAttribute = 'slug';

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'RedirectBehavior' => RedirectBehavior::class,
        ];
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
                    'when' => fn (): bool => $this->isSlugRequired()
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
                    'max' => $this->slugMaxLength,
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

        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->updateTreeBeforeSave();
        return true;
    }

    /**
     * On parent id change all related entries (linked to this category as well as to the child categories)
     * need to be added to the new parent categories, if {@see \davidhirtz\yii2\cms\Module::$inheritNestedCategories}
     * is true. Previous parent {@see EntryCategory} relations will not be deleted.
     */
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            if ($this->parent_id && array_key_exists('parent_id', $changedAttributes)) {
                $this->insertEntryCategoryAncestors();
            }
        }

        CategoryCollection::invalidateCache();

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
        /** @var EntryQuery $relation */
        $relation = $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryCategories');

        return $relation;
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
     * Inserts related {@see EntryCategory} records to this records ancestor categories. This method is called after
     * the parent id was changed and can insert quite a lot of records. This might need to be overridden on applications
     * with MANY entry-category relations.
     */
    protected function insertEntryCategoryAncestors(): void
    {
        // If the category doesn't have `inheritNestedCategories` enabled, descendant categories need to be used.
        $categoryIds = $this->inheritNestedCategories() ? $this->id : array_keys(array_filter($this->descendants, fn (self $category): bool => $category->inheritNestedCategories()));

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
     * Deletes all related entry categories before the {@see EntryCategory} records would be deleted by the database's
     * foreign key relation. This enables recalculating the related record as well as adding {@see Trail} records.
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

    public static function getBySlug(string $slug, int $parentId = null): ?self
    {
        if ($slug) {
            if (strpos($slug, '/')) {
                $prevParentId = null;
                $category = null;

                foreach (explode('/', $slug) as $part) {
                    if ($category = static::getBySlug($part, $prevParentId)) {
                        $prevParentId = $category->id;
                    }
                }

                return $category;
            }

            foreach (CategoryCollection::getAll() as $category) {
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
        return array_filter(['/cms/site/index', 'category' => $this->getI18nAttribute('slug')]);
    }

    public function getEntryOrderBy(): bool|array
    {
        return [EntryCategory::tableName() . '.[[position]]' => SORT_ASC];
    }

    public function hasDescendantsEnabled(): bool
    {
        return static::getModule()->inheritNestedCategories;
    }

    public function hasEntriesEnabled(): bool
    {
        return true;
    }

    public function hasParentEnabled(): bool
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
