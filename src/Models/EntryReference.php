<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\Interfaces\ModelReferenceInterface;
use Hirtz\Skeleton\Models\Traits\ModelReferenceTrait;
use Override;
use Yii;
use yii\db\ActiveQuery;

/**
 * One row per owner and linked entry, however often its link and HTML attributes name it (nested names joined by
 * `.`). Read by the admin alone: the frontend parses the values it renders.
 *
 * @property int $id
 * @property int $entry_id
 * @property int $use_count
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read Entry|null $entry {@see EntryReference::getEntry()}
 */
class EntryReference extends ActiveRecord implements ModelReferenceInterface
{
    use ModelReferenceTrait;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TimestampBehavior' => TimestampBehavior::class,
        ];
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$this->getIsBatch()) {
            $this->entry?->updateReferenceCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            $this->entry?->updateReferenceCount();
        }

        parent::afterDelete();
    }

    /**
     * @return ActiveQuery<Entry>
     */
    public function getEntry(): ActiveQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'model_class' => Yii::t('cms', 'ENTRY_REFERENCE_MODEL_LABEL'),
            'attribute_counts' => Yii::t('cms', 'ENTRY_REFERENCE_ATTRIBUTES_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%entry_reference}}';
    }
}
