<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Skeleton\Db\ActiveQuery;

/**
 * @template T of EntryRelation
 * @extends ActiveQuery<T>
 */
class EntryRelationQuery extends ActiveQuery
{
    /**
     * @param class-string<EntryRelationModelInterface> $modelClass
     */
    public function whereModelClass(string $modelClass): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[model_class]]' => $modelClass]);
    }

    public function whereModel(EntryRelationModelInterface $model): static
    {
        return $this->whereModels([$model]);
    }

    /**
     * @param EntryRelationModelInterface[] $models
     */
    public function whereModels(array $models): static
    {
        $alias = $this->getTableAlias();
        $ids = [];

        foreach ($models as $model) {
            $ids[$model->getEntryRelationClass()::getModelClass()][] = $model->id;
        }

        if (!$ids) {
            return $this->andWhere('0=1');
        }

        $condition = ['or'];

        foreach ($ids as $modelClass => $modelIds) {
            $condition[] = [
                "$alias.[[model_class]]" => $modelClass,
                "$alias.[[model_id]]" => array_values(array_unique($modelIds)),
            ];
        }

        return $this->andWhere($condition);
    }
}
