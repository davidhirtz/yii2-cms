<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Category;
use Yii;

/**
 * Rewrites the permalinks of a category's subtree after its own URL changed.
 *
 * {@see \Hirtz\Cms\Models\Entry} needs no equivalent: its `afterSave()` already re-saves every descendant, so their
 * permalinks follow on their own. Categories cannot use that approach, because saving a category runs
 * {@see Category::updateTreeBeforeSave()} and would disturb the nested set.
 *
 * Descendants arrive in `lft` order, so a parent is always processed before its children and can be populated as
 * their parent relation — which keeps {@see Category::getFormattedSlug()} from querying its way up the tree again.
 */
class UpdateDescendantPermalinks
{
    public function __construct(
        protected Category $model,
    ) {
    }

    public function updateDescendantPermalinks(): void
    {
        $processed = [$this->model->id => $this->model];

        foreach ($this->model->getDescendants(true) as $descendant) {
            $descendant->populateParentRelation($processed[$descendant->parent_id] ?? null);
            $descendant->savePermalinks();

            $processed[$descendant->id] = $descendant;
        }
    }

    /**
     * @param array{model: Category} $params
     */
    public static function run(array $params): static
    {
        $action = Yii::createObject(static::class, $params);
        $action->updateDescendantPermalinks();

        return $action;
    }
}
