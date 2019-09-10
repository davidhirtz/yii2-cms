<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\db\NestedTreeTrait;
use Yii;
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
 * @property Section[] $sections
 * @property Asset[] $assets
 * @property  \davidhirtz\yii2\cms\models\EntryCategory $entryCategory
 * @method static \davidhirtz\yii2\cms\models\Category findOne($condition)
 */
class Category extends ActiveRecord
{
    use NestedTreeTrait;

    /**
     * @var bool
     */
    public $customSlugBehavior = false;

    /**
     * @var bool|string
     */
    public $contentType = false;

    /**
     * @var static[]
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
                ['name', 'slug'],
                'required',
            ],
            [
                ['name', 'slug', 'title', 'description', 'content'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['slug'],
                'string',
                'max' => 100,
            ],
            [
                ['name', 'title', 'description'],
                'string',
                'max' => 250,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => ['slug', 'parent_id'],
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ]));
    }

    /**
     * @inheritDoc
     */
    public function beforeValidate(): bool
    {
        if ($this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }


    /**
     * @inheritDoc
     */
    public function beforeSave($insert): bool
    {
        $this->updateTreeBeforeSave();
        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete()
    {
        $this->deleteNestedTreeItems();
        return parent::beforeDelete();
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
        return $this->hasOne(EntryCategory::class, ['category_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return CategoryQuery
     */
    public static function find(): CategoryQuery
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @return CategoryQuery
     */
    public function findSiblings(): CategoryQuery
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
            ->enabled()
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
     * @return Category|null
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
     * @return array
     */
    public function getRoute(): array
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
     * @return array
     */
    public function getEntryOrderBy()
    {
        return [EntryCategory::tableName() . '.[[position]]' => SORT_ASC];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
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