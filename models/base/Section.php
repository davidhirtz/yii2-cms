<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionGridView;
use davidhirtz\yii2\media\models\AssetParentInterface;
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
class Section extends ActiveRecord implements AssetParentInterface
{
    /**
     * @see \yii\validators\UniqueValidator::$targetAttribute
     * @var string|array
     */
    public $slugTargetAttribute = ['entry_id', 'slug'];

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
                'max' => static::SLUG_MAX_LENGTH,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => $this->slugTargetAttribute,
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
                'when' => function () {
                    return $this->isAttributeChanged('slug');
                }
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

        // Handle section cloning...
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
     * @return bool
     */
    public function beforeDelete()
    {
        if ($isValid = parent::beforeDelete()) {
            if (!$this->entry->isDeleted()) {
                if ($this->asset_count) {
                    foreach ($this->assets as $asset) {
                        $asset->delete();
                    }
                }
            }
        }

        return $isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        if (!$this->entry->isDeleted()) {
            $this->entry->recalculateSectionCount();
        }

        parent::afterDelete();
    }

    /**
     * @return AssetQuery
     */
    public function getAssets(): AssetQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Asset::class, ['section_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');
    }

    /**
     * @return EntryQuery
     */
    public function getEntry()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::createObject(SectionQuery::class, [get_called_class()]);
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function clone($attributes = [])
    {
        $clone = new static();
        $clone->setAttributes(array_merge($this->getAttributes(), $attributes ?: ['status' => static::STATUS_DRAFT]));
        $clone->generateUniqueSlug();

        if ($clone->insert()) {
            foreach ($this->assets as $asset) {
                $assetClone = new Asset();
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
     * @return callable||string|null custom name for {@link SectionGridView::nameColumn()}
     */
    public function getNameColumnContent()
    {
        if (isset(static::getTypes()[$this->type]['nameColumn'])) {
            $nameColumn = static::getTypes()[$this->type]['nameColumn'];
            return is_callable($nameColumn) ? call_user_func($nameColumn, $this) : $nameColumn;
        }

        return null;
    }

    /**
     * @return SectionActiveForm|Widget
     */
    public function getActiveForm()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? SectionActiveForm::class;
    }

    /**
     * @return array|false
     */
    public function getRoute()
    {
        return ($route = $this->entry->getRoute()) ? array_merge($route, ['#' => $this->getI18nAttribute('slug') ?: ('section-' . $this->id)]) : false;
    }

    /**
     * @return bool
     */
    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableSectionAssets;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'entry_id' => Yii::t('cms', 'Entry'),
            'slug' => Yii::t('cms', 'Url'),
            'section_count' => Yii::t('cms', 'Sections'),
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