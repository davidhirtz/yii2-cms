<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use Yii;
use yii\base\Widget;
use yii\helpers\Inflector;

/**
 * Class Entry.
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
 * @property DateTime $publish_date
 * @property string $category_ids
 * @property int $section_count
 * @property int $asset_count
 * @property Section[] $sections
 * @property Asset[] $assets
 * @property \davidhirtz\yii2\cms\models\Entry $entry
 * @property EntryCategory $entryCategory
 * @method static \davidhirtz\yii2\cms\models\Entry findOne($condition)
 */
class Entry extends ActiveRecord
{
    /**
     * @var bool
     */
    public $customSlugBehavior = false;

    /**
     * @var bool|string
     */
    public $contentType = false;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
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
                'targetAttribute' => static::getModule()->enabledNestedEntries ? ['slug', 'parent_id'] : 'slug',
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
            [
                ['publish_date'],
                DateTimeValidator::class,
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (!$this->slug) {
            $this->slug = $this->name;
        }

        if (!$this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }


    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if (!$this->publish_date) {
            $this->publish_date = new DateTime;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntryCategory()
    {
        return $this->hasOne(EntryCategory::class, ['entry_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
//    public function getCategories()
//    {
//        return $this->hasMany(\davidhirtz\yii2\cms\models\Category::class, ['id' => 'category_id'])
//            ->viaTable(EntryCategory::tableName(), ['entry_id' => 'id']);
//    }

    /**
     * @return SectionQuery
     */
    public function getSections(): SectionQuery
    {
        return $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->enabled()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @return AssetQuery
     */
    public function getAssets(): AssetQuery
    {
        return $this->hasMany(Asset::class, ['entry_id' => 'id'])
            ->enabled()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @inheritdoc
     * @return EntryQuery
     */
    public static function find(): EntryQuery
    {
        return new EntryQuery(get_called_class());
    }

    /**
     * @return EntryQuery
     */
    public function findSiblings(): EntryQuery
    {
        return static::getModule()->enabledNestedEntries ? $this->find()->where(['parent_id' => $this->parent_id]) : $this->find();
    }

    /**
     * @param Asset[] $assets
     */
    public function populateAssetRelations($assets = null)
    {
        if ($assets === null) {
            $assets = $this->assets;
        }

        $relations = [];

        if ($assets) {
            foreach ($assets as $asset) {
                if ($asset->entry_id == $this->id && !$asset->section_id) {
                    $asset->populateRelation('entry', $this);
                    $relations[$asset->id] = $asset;
                }
            }
        }

        $this->populateRelation('assets', $relations);

        if (static::getModule()->enableSections && $this->isRelationPopulated('sections')) {
            foreach ($this->sections as $section) {
                $section->populateAssetRelations($assets);
            }
        }
    }

    /**
     * Updates category ids.
     */
    public function recalculateCategoryIds()
    {
        $categoryIds = EntryCategory::find()->select(['category_id'])->where(['entry_id' => $this->id])->column();
        $this->category_ids = implode(',', $categoryIds);
        $this->update(false, ['category_ids', 'updated_at', 'updated_by_user_id']);
    }

    /**
     * @return false|int
     */
    public function recalculateSectionCount()
    {
        $this->section_count = $this->getSections()->count();
        return $this->update(false);
    }

    /**
     * @return array
     */
    public function getCategoryIds(): array
    {
        return array_filter(explode(',', $this->category_ids));
    }

    /**
     * @return int
     */
    public function getCategoryCount(): int
    {
        return count($this->getCategoryIds());
    }

    /**
     * @return array
     */
    public function getRoute(): array
    {
        return array_filter(['/cms/site/view', 'entry' => $this->getI18nAttribute('slug')]);
    }

    /**
     * @return EntryActiveForm|Widget
     */
    public function getActiveForm()
    {
        return static::getTypes()[$this->type]['activeForm'] ?? EntryActiveForm::class;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'publish_date' => Yii::t('cms', 'Published'),
            'section_count' => Yii::t('cms', 'Sections'),
        ]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Entry';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry');
    }
}