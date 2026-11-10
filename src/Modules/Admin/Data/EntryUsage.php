<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Yii;
use yii\base\Model;

/**
 * A record using an entry: linking it (`entry_reference`), listing it (`entry_relation`) or both.
 */
class EntryUsage extends Model
{
    public string $id;
    public ?ActiveRecord $model = null;
    public bool $listed = false;

    /**
     * @var array<string, int>
     */
    public array $linkCounts = [];

    public ?DateTime $updated_at = null;

    public function __construct(public readonly string $model_class, public readonly int $model_id, array $config = [])
    {
        $this->id = "$model_class:$model_id";
        parent::__construct($config);
    }

    /**
     * One query per owner class on each side, newest first.
     *
     * @return list<self>
     */
    public static function findAll(Entry $entry): array
    {
        $usages = [];

        $references = $entry->getReferences()->all();
        EntryReference::populateModelRelations($references);

        foreach ($references as $reference) {
            $usage = $usages["$reference->model_class:$reference->model_id"]
                ??= new self($reference->model_class, $reference->model_id);

            $usage->model = $reference->getModel();
            $usage->linkCounts = $reference->getAttributeLabelCounts();
            $usage->updated_at = self::max($usage->updated_at, $reference->updated_at);
        }

        $relations = EntryRelation::find()->where(['entry_id' => $entry->id])->all();
        $modelIds = [];

        foreach ($relations as $relation) {
            $modelIds[$relation->model_class][] = (int)$relation->model_id;
        }

        foreach ($modelIds as $modelClass => $ids) {
            $models = class_exists($modelClass) && is_subclass_of($modelClass, ActiveRecord::class)
                ? Yii::createObject($modelClass)::find()->andWhere(['id' => $ids])->indexBy('id')->all()
                : [];

            foreach ($relations as $relation) {
                if ($relation->model_class !== $modelClass) {
                    continue;
                }

                $usage = $usages["$modelClass:$relation->model_id"] ??= new self($modelClass, (int)$relation->model_id);
                $model = $models[$relation->model_id] ?? null;

                $usage->model ??= $model instanceof ActiveRecord ? $model : null;
                $usage->listed = true;
                $usage->updated_at = self::max($usage->updated_at, $relation->updated_at);
            }
        }

        $usages = array_values($usages);
        usort($usages, static fn (self $a, self $b): int => ($b->updated_at?->getTimestamp() ?? 0) <=> ($a->updated_at?->getTimestamp() ?? 0));

        return $usages;
    }

    private static function max(?DateTime $a, ?DateTime $b): ?DateTime
    {
        return $a === null || ($b !== null && $b > $a) ? $b : $a;
    }
}
