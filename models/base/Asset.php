<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\AssetActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetParentGridView;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\AssetInterface;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\traits\AssetTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\ModelEvent;
use yii\base\Widget;

/**
 * Class Asset
 * @package davidhirtz\yii2\cms\models\base
 * @see \davidhirtz\yii2\cms\models\Asset
 *
 * @property int $id
 * @property int $entry_id
 * @property int $section_id
 * @property int $file_id
 * @property int $position
 * @property string $name
 * @property string $content
 * @property string $alt_text
 * @property string $link
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @property Entry $entry
 * @property Section $section
 * @property File $file
 * @property User $updated
 *
 * @method static \davidhirtz\yii2\cms\models\Asset findOne($condition)
 */
class Asset extends ActiveRecord implements AssetInterface
{
    use AssetTrait;

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['section_id'],
                'validateSectionId',
            ],
            [
                ['file_id', 'entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                'validateEntryId',
            ],
            [
                $this->getI18nAttributesNames(['name', 'alt_text', 'link']),
                'string',
                'max' => 250,
            ],
        ]);
    }

    /**
     * Validates section relation and sets entry relation, thus this needs to be called before entry validation. As
     * this method gets skipped on empty `section_id` this only sets the relation while {@link Section::validateEntryId()}
     * will validate the section's entry_id.
     */
    public function validateSectionId()
    {
        if ($this->section) {
            $this->populateEntryRelation($this->section->entry);
        }
    }

    /**
     * Validates entry relation.
     */
    public function validateEntryId()
    {
        if (!$this->entry || (!$this->getIsNewRecord() && $this->isAttributeChanged('entry_id'))) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $parent = $this->getParent();
            $parent->asset_count = $this->findSiblings()->count();
            $parent->update();

            $this->updateOrDeleteFileByAssetCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        // Entry needs to be checked separately here because Entry::beforeDelete() deletes
        // all related assets before deleting the sections.
        if (!$this->entry->isDeleted() && (!$this->section_id || !$this->section->isDeleted())) {
            $parent = $this->getParent();
            $parent->asset_count = $this->findSiblings()->count();
            $parent->update();
        }

        $this->updateOrDeleteFileByAssetCount();

        parent::afterDelete();
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
    public function getSection()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Section::class, ['id' => 'section_id']);
    }

    /**
     * @return AssetQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['entry_id' => $this->entry_id, 'section_id' => $this->section_id]);
    }

    /**
     * @return AssetQuery
     */
    public static function find()
    {
        return Yii::createObject(AssetQuery::class, [get_called_class()]);
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function clone($attributes = [])
    {
        $clone = new \davidhirtz\yii2\cms\models\Asset();
        $clone->setAttributes(array_merge($this->getAttributes(), $attributes));

        if ($clone->insert()) {
            $this->afterClone($clone);
        }

        return $clone;
    }

    /**
     * @param Entry $entry
     */
    public function populateEntryRelation($entry)
    {
        $this->populateRelation('entry', $entry);
        $this->entry_id = $entry->id;
    }

    /**
     * @param Section $section
     */
    public function populateSectionRelation($section)
    {
        if ($section) {
            $this->populateRelation('entry', $section->entry);
        }

        $this->populateRelation('section', $section);
        $this->section_id = $section->id ?? null;
    }

    /**
     * @return array
     */
    public function getTrailParents()
    {
        return $this->isSectionAsset() ? [$this->section, $this->entry, $this->file] : [$this->entry, $this->file];
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return Yii::t('cms', 'Asset');
    }

    /**
     * @return AssetActiveForm|Widget
     */
    public function getActiveForm()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? AssetActiveForm::class;
    }

    /**
     * @return Entry|Section
     */
    public function getParent()
    {
        return $this->section_id ? $this->section : $this->entry;
    }

    /**
     * @return string
     */
    public function getParentGridView(): string
    {
        return AssetParentGridView::class;
    }

    /**
     * @return mixed|string
     */
    public function getParentName(): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        return $module->name;
    }

    /**
     * @return string
     */
    public function getFileCountAttribute(): string
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getAttributeName('cms_asset_count') : 'cms_asset_count';
    }

    /**
     * @return array|false
     */
    public function getAdminRoute()
    {
        return $this->id ? ['/admin/cms/asset/update', 'id' => $this->id] : false;
    }

    /**
     * @return false|mixed
     */
    public function getRoute()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isEntryAsset(): bool
    {
        return !$this->section_id;
    }

    /**
     * @return bool
     */
    public function isSectionAsset(): bool
    {
        return (bool)$this->section_id;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'section_id' => Yii::t('cms', 'Section'),
            'file_id' => Yii::t('media', 'File'),
            'alt_text' => Yii::t('cms', 'Alt text'),
            'link' => Yii::t('cms', 'Link'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('cms_asset');
    }
}