<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Category;


class UpdateDescendantPermalinks
{
    public function __construct(
        protected Category $model,
    ) {
    }

    public function update(): void
    {
        $processed = [$this->model->id => $this->model];

        foreach ($this->model->getDescendants(true) as $descendant) {
            $descendant->populateParentRelation($processed[$descendant->parent_id] ?? null);
            $descendant->savePermalinks();

            $processed[$descendant->id] = $descendant;
        }
    }
}
