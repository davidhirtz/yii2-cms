<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Media\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @property Asset|Category|Entry|Section|null $model
 */
class AdminLink extends Widget
{
    use TagAttributesTrait;

    protected Asset|Category|Entry|Section $model;

    public function model(Asset|Category|Entry|Section $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['class'] ??= 'admin overlay';
        $this->attributes['target'] ??= '_blank';

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $route = $this->canUpdateModel() ? $this->model->getAdminRoute() : null;
        return $route ? A::make()->attributes($this->attributes)->href($route) : '';
    }

    protected function canUpdateModel(): bool
    {
        if ($this->model instanceof Entry) {
            return $this->webuser->can(Entry::AUTH_ENTRY);
        }

        if ($this->model instanceof Section) {
            return $this->webuser->can(Entry::AUTH_ENTRY);
        }

        if ($this->model instanceof Category) {
            return $this->webuser->can(Category::AUTH_CATEGORY);
        }

        return $this->webuser->can($this->model->getPermissionName());
    }

    public static function tag(Asset|Category|Entry|Section $model): string
    {
        return self::make()->model($model)->render();
    }
}
