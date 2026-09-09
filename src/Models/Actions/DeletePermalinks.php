<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\ActiveRecord;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Yii;

/**
 * Deletes all {@see Permalink} records of a model. The relation is polymorphic, so this cannot be a foreign key
 * cascade.
 */
class DeletePermalinks
{
    public function __construct(
        protected ActiveRecord&PermalinkInterface $model,
    ) {
    }

    public function deletePermalinks(): void
    {
        foreach ($this->model->getPermalinks()->all() as $permalink) {
            $permalink->delete();
        }

        $this->model->populateRelation('permalinks', []);
    }

    /**
     * @param array{model: ActiveRecord&PermalinkInterface} $params
     */
    public static function run(array $params): static
    {
        $action = Yii::createObject(static::class, $params);
        $action->deletePermalinks();

        return $action;
    }
}
