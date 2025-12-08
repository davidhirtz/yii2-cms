<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Cms\Models\Traits\EntryRelationTrait;
use Hirtz\Cms\Models\Traits\SectionRelationTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Behaviors\BlameableBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Models\Traits\UpdatedByUserTrait;
use Hirtz\Skeleton\Validators\RelationValidator;
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
class SectionEntry extends \Hirtz\Skeleton\Db\ActiveRecord
{
    use EntryRelationTrait;
    use ModuleTrait;
    use SectionRelationTrait;
    use UpdatedByUserTrait;

    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[\Override]
    public function rules(): array
    {
        return [...parent::rules(), [
            ['section_id'],
            RelationValidator::class,
            'required' => true,
        ], [
            ['entry_id'],
            RelationValidator::class,
            'required' => true,
        ], [
            ['entry_id'],
            'unique',
            'targetAttribute' => ['section_id', 'entry_id'],
        ], [
            ['entry_id'],
            $this->validateEntry(...),
        ]];
    }

    protected function validateEntry(): void
    {
        if ($this->hasErrors('entry_id')) {
            return;
        }

        $allowedTypes = $this->section->getEntriesTypes();

        if ($allowedTypes && !in_array($this->entry->type, $allowedTypes, true)) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    #[\Override]
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

    #[\Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert && !$this->getIsBatch()) {
            $this->updateSectionEntryCount();
            static::getModule()->invalidatePageCache();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[\Override]
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

    #[\Override]
    public function attributeLabels(): array
    {
        return [...parent::attributeLabels(), 'section_id' => Yii::t('cms', 'Section'), 'entry_id' => Yii::t('cms', 'Entry'), 'updated_at' => Yii::t('cms', 'Added')];
    }

    #[\Override]
    public function formName(): string
    {
        return 'SectionEntry';
    }

    #[\Override]
    public static function tableName(): string
    {
        return static::getModule()->getTableName('section_entry');
    }
}
