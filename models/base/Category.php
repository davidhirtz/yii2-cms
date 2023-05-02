<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\CategoryActiveForm;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\db\NestedTreeTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\base\Widget;
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
 * @property Section[] $sections
 * @property Asset[] $assets
 * @property Entry[] $entry {@see \davidhirtz\yii2\cms\models\Category::getEntries()}
 * @property EntryCategory $entryCategory {@see \davidhirtz\yii2\cms\models\Category::getEntryCategory()}
 * @property EntryCategory[] $entryCategories {@see \davidhirtz\yii2\cms\models\Category::getEntryCategories()}
 * @property \davidhirtz\yii2\cms\models\Category $parent {@see \davidhirtz\yii2\cms\models\Category::getParent()}
 * @property \davidhirtz\yii2\cms\models\Category[] $ancestors
 * @property \davidhirtz\yii2\cms\models\Category[] $descendants
 *
 * @method \davidhirtz\yii2\cms\models\Category[] getAncestors($refresh = false)
 * @method \davidhirtz\yii2\cms\models\Category[] getDescendants($refresh = false)
 * @method static \davidhirtz\yii2\cms\models\Category findOne($condition)
 * @method static \davidhirtz\yii2\cms\models\Category[] findAll($condition)
 */
class Category extends ActiveRecord
{
    use NestedTreeTrait;

    /**
     * @var bool|string
     */
    public $contentType = false;

    /**
     * @see \yii\validators\UniqueValidator::$targetAttribute
     * @var string|array
     */
    public $slugTargetAttribute = ['parent_id', 'slug'];

    /**
     * @var \davidhirtz\yii2\cms\models\Category[]
     * @see Category::getCategories()
     */
    protected static $_categories;

    /**
     * Cache.
     */
    public const CATEGORIES_CACHE_KEY = 'get-categories-cache';

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'RedirectBehavior' => RedirectBehavior::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['parent_id'],
                'number',
                'integerOnly' => true,
            ],
            [
                ['parent_id'],
                'validateParentId',
                'skipOnEmpty' => false,
            ],
            [
                ['name'],
                'required',
            ],
            [
                ['slug'],
                'required',
                'when' => function () {
                    return $this->isSlugRequired();
                }
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
        ]));
    }

    /**
     * @inheritDoc
     */
    public function beforeValidate(): bool
    {
        $this->ensureSlug();

        if (!static::getModule()->enableNestedCategories) {
            $this->parent_id = null;
        }

        return parent::beforeValidate();
    }


    /**
     * @inheritDoc
     */
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
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            if ($this->parent_id && array_key_exists('parent_id', $changedAttributes)) {
                $this->insertEntryCategoryAncestors();
            }
        }

        static::invalidateCategoriesCache();

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete()
    {
        if ($isValid = parent::beforeDelete()) {
            $this->deleteNestedTreeItems();

            if ($this->entry_count) {
                $this->deleteEntryCategories();
            }
        }

        return $isValid;
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        $this->updateNestedTreeAfterDelete();
        parent::afterDelete();
    }

    /**
     * @return ActiveQuery
     */
    public function getEntryCategory()
    {
        return $this->hasOne(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    /**
     * @return EntryQuery
     */
    public function getEntries()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryCategory');
    }

    /**
     * @return ActiveQuery
     */
    public function getEntryCategories()
    {
        return $this->hasMany(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    /**
     * Updates {@link \davidhirtz\yii2\cms\models\Category::$entry_count}.
     * @return $this
     */
    public function recalculateEntryCount()
    {
        $this->entry_count = (int)$this->getEntryCategories()->count();
        return $this;
    }

    /**
     * Inserts related {@link EntryCategory} records to this records ancestor categories. This method is called after
     * the parent id was changed and can insert quite a lot of records. This might need to be overridden on applications
     * with MANY entry-category relations.
     */
    protected function insertEntryCategoryAncestors()
    {
        // If the category doesn't have `inheritNestedCategories` enabled descendant categories need to be used.
        $categoryIds = $this->inheritNestedCategories() ? $this->id : array_keys(array_filter($this->getDescendants(), function (self $category) {
            return $category->inheritNestedCategories();
        }));

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

                    /** @noinspection PhpParamsInspection */
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
    protected function deleteEntryCategories()
    {
        $entryCategories = $this->getEntryCategories()
            ->with('entry')
            ->all();

        foreach ($entryCategories as $entryCategory) {
            $entryCategory->delete();
        }
    }

    /**
     * @inheritdoc
     * @return CategoryQuery
     */
    public static function find()
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @return CategoryQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return ActiveQuery
     */
    public function getSitemapQuery()
    {
        return static::find()
            ->selectSitemapAttributes()
            ->orderBy(['id' => SORT_ASC]);
    }

    /**
     * @return static[]
     */
    public static function findCategories()
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
    public static function getCategories()
    {
        if (static::$_categories === null) {
            $dependency = new TagDependency(['tags' => static::CATEGORIES_CACHE_KEY]);
            static::$_categories = static::getModule()->categoryCachedQueryDuration > 0 ? static::getDb()->cache([static::class, 'findCategories'], static::getModule()->categoryCachedQueryDuration, $dependency) : static::findCategories();
        }

        return static::$_categories;
    }

    /**
     * Invalidates cache.
     */
    public static function invalidateCategoriesCache()
    {
        if (static::getModule()->categoryCachedQueryDuration > 0) {
            TagDependency::invalidate(Yii::$app->getCache(), static::CATEGORIES_CACHE_KEY);
        }
    }

    /**
     * @param array $entryIds
     */
    public function updateEntryOrder($entryIds)
    {
        $entries = $this->getEntryCategories()
            ->andWhere(['entry_id' => $entryIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        if (EntryCategory::updatePosition($entries, array_flip($entryIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Entry order changed'));
        }
    }

    /**
     * @param string $slug
     * @param int|null $parentId
     * @return \davidhirtz\yii2\cms\models\Category|null
     */
    public static function getBySlug($slug, $parentId = null)
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

    /**
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'lft',
            'rgt',
            'entry_count',
        ]);
    }

    /**
     * @return string
     */
    public function getTrailModelName()
    {
        if ($this->id) {
            return $this->getI18nAttribute('name') ?: Yii::t('skeleton', '{model} #{id}', [
                'model' => $this->getTrailModelType(),
                'id' => $this->id,
            ]);
        }

        return $this->getTrailModelType();
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'Category');
    }

    /**
     * @return array|false
     */
    public function getAdminRoute()
    {
        return $this->id ? ['/admin/category/update', 'id' => $this->id] : false;
    }

    /**
     * @return array|false
     */
    public function getRoute()
    {
        return array_filter(['/cms/site/index', 'category' => $this->getNestedSlug()]);
    }

    /**
     * @return string
     */
    public function getNestedSlug()
    {
        $slugs = [];

        foreach ($this->getAncestors() as $ancestor) {
            $slugs[] = $ancestor->getI18nAttribute('slug');
        }

        return implode('/', array_merge($slugs, [$this->getI18nAttribute('slug')]));
    }

    /**
     * @return array|false
     */
    public function getEntryOrderBy()
    {
        return [EntryCategory::tableName() . '.[[position]]' => SORT_ASC];
    }

    /**
     * @return CategoryActiveForm|Widget
     */
    public function getActiveForm()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? CategoryActiveForm::class;
    }

    /**
     * @return bool
     */
    public function hasEntriesEnabled(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function inheritNestedCategories(): bool
    {
        return static::getModule()->inheritNestedCategories;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'name' => Yii::t('cms', 'Name'),
            'parent_id' => Yii::t('cms', 'Category'),
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'branchCount' => Yii::t('cms', 'Subcategories'),
            'entry_count' => Yii::t('cms', 'Entries'),
        ]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Category';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('category');
    }
}