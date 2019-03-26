<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use Yii;
use yii\helpers\Inflector;

/**
 * Class Section.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $entry_id
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $content
 * @property int $asset_count
 * @property Entry $entry
 * @property Asset[] $assets
 * @method static \davidhirtz\yii2\cms\models\Section findOne($condition)
 */
class Section extends ActiveRecord
{
    /**
     * @return EntryQuery
     */
    public function getEntry(): EntryQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                'filter',
                'filter' => 'intval',
            ],
            [
                ['entry_id'],
                'validateEntryId',
            ],
            [
                $this->getI18nAttributeNames(['name', 'slug', 'content']),
                'filter',
                'filter' => 'trim',
            ],
            [
                $this->getI18nAttributeNames(['slug']),
                'string',
                'max' => 100,
            ],
            [
                $this->getI18nAttributeNames(['name']),
                'string',
                'max' => 250,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => ['slug', 'entry_id'],
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ]));
    }

    /**
     * Validates entry and populates relation.
     */
    public function validateEntryId()
    {
        if (!$this->entry || (!$this->getIsNewRecord() && $this->isAttributeChanged('entry_id'))) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->slug) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!$this->slug) {
            $this->slug = null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $this->entry->recalculateSectionCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        $this->entry->recalculateSectionCount();
        parent::afterDelete();
    }

    /**
     * @return AssetQuery
     */
    public function getAssets(): AssetQuery
    {
        return $this->hasMany(Asset::class, ['section_id' => 'id'])
            ->enabled()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');
    }

    /**
     * @return SectionQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['entry_id' => $this->entry_id]);
    }

    /**
     * @return SectionQuery
     */
    public static function find()
    {
        return Yii::createObject(SectionQuery::class, [get_called_class()]);
    }

    /**
     * @param Asset[] $assets
     */
    public function populateAssetRelations($assets)
    {
        $relations = [];

        if ($assets) {
            foreach ($assets as $asset) {
                if ($asset->section_id == $this->id) {
                    $asset->populateRelation('section', $this);
                    $relations[$asset->id] = $asset;
                }
            }
        }

        $this->populateRelation('assets', $relations);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'entry_id' => Yii::t('skeleton', 'Entry'),
            'slug' => Yii::t('cms', 'Url'),
            'section_count' => Yii::t('skeleton', 'Sections'),
        ]);
    }

    /**
     * @return string
     */
    public function formName()
    {
        return 'Section';
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::getModule()->getTableName('section');
    }
}