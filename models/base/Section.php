<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\media\models\AssetRelationInterface;
use Yii;
use yii\base\Widget;
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
class Section extends ActiveRecord implements AssetRelationInterface
{
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
                ['name', 'slug', 'content'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['name'],
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
                'unique',
                'targetAttribute' => ['slug', 'entry_id'],
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ]));
    }

    /**
     * Validates entry.
     */
    public function validateEntryId()
    {
        if (($this->isAttributeChanged('entry_id') && !$this->refreshRelation('entry')) || !$this->entry->hasSectionsEnabled()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->slug && !$this->customSlugBehavior) {
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

        if (!$insert && $this->isAttributeChanged('entry_id')) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * Updates related entries after save.
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (array_key_exists('entry_id', $changedAttributes)) {
            if (!empty($changedAttributes['entry_id'])) {
                if ($entry = Entry::findOne($changedAttributes['entry_id'])) {
                    $entry->recalculateSectionCount();
                }
            }

            if ($this->asset_count) {
                Asset::updateAll(['entry_id' => $this->entry_id], ['section_id' => $this->id]);
            }

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
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');
    }

    /**
     * @return EntryQuery
     */
    public function getEntry(): EntryQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
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
     * @param array $attributes
     * @return $this
     */
    public function clone($attributes = [])
    {
        $clone = new static;
        $clone->setAttributes(array_merge($this->getAttributes(), $attributes ?: ['status' => static::STATUS_DRAFT]));
        $clone->generateUniqueSlug();

        if ($clone->insert()) {
            foreach ($this->assets as $asset) {
                $assetClone = new Asset;
                $assetClone->setAttributes(array_merge($asset->getAttributes(), ['section_id' => $clone->id]));
                $assetClone->populateRelation('section', $clone);
                $assetClone->insert();
            }
        }

        return $clone;
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
     * @return bool
     */
    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableSectionAssets;
    }

    /**
     * @return SectionActiveForm|Widget
     */
    public function getActiveForm()
    {
        return static::getTypes()[$this->type]['activeForm'] ?? SectionActiveForm::class;
    }

    /**
     * @return array|false
     */
    public function getRoute()
    {
        return array_merge($this->entry->getRoute(), ['#' => $this->getI18nAttribute('slug')]);
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