<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Override;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * One row per owner and linked entry, however often its link and HTML attributes name it (nested names joined by
 * `.`). Read by the admin alone: the frontend parses the values it renders.
 *
 * @property int $id
 * @property int $entry_id
 * @property string $model_class
 * @property int $model_id
 * @property int $use_count
 * @property array<string, int>|null $attribute_counts
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read Entry|null $entry {@see EntryReference::getEntry()}
 */
class EntryReference extends ActiveRecord
{
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

    /**
     * The owner, `null` when it is gone or its class is.
     */
    public function getModel(): ?ActiveRecord
    {
        if (!$this->isRelationPopulated('model')) {
            static::populateModelRelations([$this]);
        }

        $model = $this->getRelatedRecords()['model'] ?? null;

        return $model instanceof ActiveRecord ? $model : null;
    }

    public function populateModelRelation(?ActiveRecord $model): void
    {
        $this->populateRelation('model', $model);
    }

    /**
     * One query per class.
     *
     * @param array<EntryReference> $references
     */
    public static function populateModelRelations(array $references): void
    {
        $modelIds = [];

        foreach ($references as $reference) {
            if (!$reference->isRelationPopulated('model')) {
                $modelIds[$reference->model_class][$reference->model_id] = $reference->model_id;
            }
        }

        foreach ($modelIds as $modelClass => $ids) {
            $models = class_exists($modelClass) && is_subclass_of($modelClass, ActiveRecord::class)
                ? Yii::createObject($modelClass)::find()->andWhere(['id' => array_values($ids)])->indexBy('id')->all()
                : [];

            foreach ($references as $reference) {
                if ($reference->model_class === $modelClass && !$reference->isRelationPopulated('model')) {
                    $model = $models[$reference->model_id] ?? null;
                    $reference->populateModelRelation($model instanceof ActiveRecord ? $model : null);
                }
            }
        }
    }

    /**
     * By the owner's labels (`Content (DE)`, `Links › Link`).
     *
     * @return array<string, int>
     */
    public function getAttributeLabelCounts(): array
    {
        $model = $this->getModel();
        $labels = [];

        foreach ($this->attribute_counts ?? [] as $name => $count) {
            $path = explode('.', (string)$name);
            $label = $model ? implode(' › ', static::getPathLabels($model, $path)) : (string)$name;
            $labels[$label] = ($labels[$label] ?? 0) + (int)$count;
        }

        return $labels;
    }

    /**
     * @param list<string> $path
     * @return list<string>
     */
    protected static function getPathLabels(Model $owner, array $path): array
    {
        $name = array_shift($path);

        if ($name === null) {
            return [];
        }

        $label = $owner->getAttributeLabel($name);
        $definition = $owner instanceof CustomAttributeInterface ? $owner->getCustomAttribute($name) : null;

        if (!$path) {
            return [$label];
        }

        return $definition instanceof GroupCustomAttribute
            ? [$label, ...static::getPathLabels($definition->createItem($owner, '0'), $path)]
            : [$label, ...$path];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%entry_reference}}';
    }
}
