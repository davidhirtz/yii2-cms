<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Yii;

/**
 * @property Category|Entry|Section $model
 */
trait FrontendUrlTrait
{
    use ModelTrait;

    #[Configure]
    protected function configureDefaultUrl(): void
    {
        if ($this->url !== null) {
            return;
        }

        $route = $this->model->getRoute();

        if ($route === false) {
            return;
        }

        $manager = Yii::$app->getUrlManager();
        $this->url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);
    }

    protected function isDisabled(): bool
    {
        return $this->model->isDisabled() || $this->entryHasStatus(Entry::STATUS_DISABLED);
    }

    protected function isDraft(): bool
    {
        return $this->model->isDisabled() || $this->entryHasStatus(Entry::STATUS_DRAFT);
    }

    protected function entryHasStatus(int $status): bool
    {
        return $this->model instanceof Entry && $this->model->status === $status;
    }
}
