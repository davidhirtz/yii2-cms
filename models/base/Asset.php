<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\queries\FileQuery;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * Class Asset.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $id
 * @property int $entry_id
 * @property int $section_id
 * @property int $file_id
 * @property int $position
 * @property string $name
 * @property string $content
 * @property string $alt_text
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property Entry $entry
 * @property Section $section
 * @property File $file
 * @property User $updated
 *
 * @method static \davidhirtz\yii2\cms\models\Asset findOne($condition)
 */
class Asset extends \davidhirtz\yii2\cms\models\base\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['section_id'],
                'validateSectionId',
            ],
            [
                ['entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                'validateEntryId',
            ],
        ]);
    }

    /**
     * Validates section relation and sets entry id, thus this needs to be called before entry validation.
     */
    public function validateSectionId()
    {
        if ($this->section) {
            if ($this->getIsNewRecord()) {
                $this->entry_id = $this->section->entry_id;
            } elseif ($this->isAttributeChanged('entry_id')) {
                $this->addInvalidAttributeError('section_id');
            }
        }
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
    public function afterSave($insert, $changedAttributes)
    {
        if($insert) {
            $this->recalculateParentAssetCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        $this->recalculateParentAssetCount();
        parent::afterDelete();
    }

    /**
     * @return EntryQuery
     */
    public function getEntry(): EntryQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSection(): ActiveQuery
    {
        return $this->hasOne(Section::class, ['id' => 'section_id']);
    }

    /**
     * @return FileQuery
     */
    public function getFile(): FileQuery
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['entry_id' => $this->entry_id, 'section_id' => $this->section_id]);
    }

    /**
     * Recalculates the parent's media count.
     */
    public function recalculateParentAssetCount()
    {
        $parent = $this->getParent();
        $parent->asset_count = $this->findSiblings()->count();
        $parent->update(false);
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
    public function formName(): string
    {
        return 'Asset';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('cms_asset');
    }
}