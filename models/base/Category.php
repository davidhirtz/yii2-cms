<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\CategoryActiveForm;
use davidhirtz\yii2\skeleton\db\NestedTreeTrait;
use Yii;
use yii\base\Widget;
use yii\caching\TagDependency;
use yii\helpers\Inflector;

/**
 * Class Category.
 * @package davidhirtz\yii2\cms\models\base
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
 * @method \davidhirtz\yii2\cms\models\Category[] getAncestors()
 * @method \davidhirtz\yii2\cms\models\Category[] getDescendants()
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
    const CATEGORIES_CACHE_KEY = 'get-categories-cache';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
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
                'filter',
                'filter' => 'trim',
            ],
            [
                ['name', 'title', 'description'],
                'string',
                'max' => 250,
            ],
            [
                ['slug'],
                'string',
                'max' => 100,
            ],
            [
                ['slug'],
                $this->slugUniqueValidator,
                'targetAttribute' => $this->slugTargetAttribute,
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ]));
    }

    /**
     * @inheritDoc
     */
    public function beforeValidate(): bool
    {
        if (!$this->slug && $this->isSlugRequired()) {
            $this->slug = $this->name;
        }

        if (!$this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
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
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            if (array_key_exists('parent_id', $changedAttributes)) {
                if ($this->entry_count) {
                    /** @var EntryCategory[] $entryCategories */
                    $entryCategories = $this->getEntryCategories()
                        ->with('entry')
                        ->all();

                    foreach ($entryCategories as $entryCategory) {
                        $entryCategory->populateRelation('category', $this);
                        $entryCategory->insertCategoryAncestors();
                        $entryCategory->entry->recalculateCategoryIds();
                    }
                }
            }
        }

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
                foreach ($this->entryCategories as $entryCategory) {
                    $entryCategory->delete();
                }
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
     * @return \yii\db\ActiveQuery
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
        return $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryCategory');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntryCategories()
    {
        return $this->hasMany(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    /**
     * Updates entry count.
     */
    public function recalculateEntryCount()
    {
        $this->entry_count = $this->getEntryCategories()->count();
        $this->update(false, ['entry_count', 'updated_at', 'updated_by_user_id']);
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
        return $this->find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return static[]
     */
    public static function findCategories()
    {
        return static::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
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
     * @param string $slug
     * @param int $parentId
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
                if ($category->slug == $slug && ($category->parent_id == $parentId)) {
                    return $category;
                }
            }
        }

        return null;
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

        /** @var static $ancestor */
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
        return static::getModule()->inheritNestedCategories && $this->hasEntriesEnabled();
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