<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;

trait FrontendUrlTrait
{
    /**
     * @use ModelTrait<Category|Entry|Section>
     */
    use ModelTrait;

    #[Configure]
    protected function configureDefaultUrl(): void
    {
        if ($this->url !== null) {
            return;
        }

        $url = $this->isDraft() ? $this->model->getDraftUrl() : $this->model->getUrl(true);

        if ($url !== false) {
            $this->url = $url;
        }
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
