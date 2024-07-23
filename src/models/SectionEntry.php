<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\models\traits\SectionRelationTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
use Yii;

/**
 * Represents a relation between a section and an entry.
 *
 * @property int $id
 * @property int $section_id
 * @property int $entry_id
 * @property int $position
 * @property int|null $updated_by_user_id
 * @property DateTime $updated_at
 */
class SectionEntry extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use EntryRelationTrait;
    use ModuleTrait;
    use SectionRelationTrait;
    use UpdatedByUserTrait;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['section_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['entry_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['entry_id'],
                'unique',
                'targetAttribute' => ['section_id', 'entry_id'],
            ],
            [
                ['entry_id'],
                $this->validateEntry(...),
            ],
        ]);
    }

    protected function validateEntry(): void
    {
        if ($this->hasErrors('entry_id')) {
            return;
        }

        $allowedTypes = $this->section->getEntriesTypes();

        if ($allowedTypes && !in_array($this->entry->type, $allowedTypes)) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => null,
            ],
        ]);

        $this->position ??= $this->getMaxPosition() + 1;

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert && !$this->getIsBatch()) {
            $this->updateSectionEntryCount();
            static::getModule()->invalidatePageCache();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            if (!$this->section->isDeleted()) {
                $this->updateSectionEntryCount();
            }

            static::getModule()->invalidatePageCache();
        }

        parent::afterDelete();
    }

    protected function updateSectionEntryCount(): bool|int
    {
        return $this->section->recalculateEntryCount()->update();
    }

    public function getMaxPosition(): int
    {
        return (int)static::find()->where(['section_id' => $this->section_id])->max('[[position]]');
    }

    /**
     * @noinspection PhpUnused
     */
    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'id',
            'position',
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    /**
     * @noinspection PhpUnused
     */
    public function getTrailParents(): ?array
    {
        return [$this->entry, $this->section];
    }

    public function getTrailModelName(): string
    {
        return Yii::t('cms', 'Section-Entry');
    }

    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'Relation');
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'section_id' => Yii::t('cms', 'Section'),
            'entry_id' => Yii::t('cms', 'Entry'),
            'updated_at' => Yii::t('cms', 'Added'),
        ]);
    }

    public function formName(): string
    {
        return 'SectionEntry';
    }

    public static function tableName(): string
    {
        return static::getModule()->getTableName('section_entry');
    }
}
