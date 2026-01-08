<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

/**
 * @property Asset|Entry|Section|null $model
 */
class AdminLink extends Widget
{
    use TagAttributesTrait;

    protected Asset|Entry|Section $model;

    public function model(Asset|Entry|Section $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->attributes['class'] ??= 'admin overlay';
        $this->attributes['target'] ??= '_blank';

        parent::configure();
    }

    public function renderContent(): string|Stringable
    {
        $route = $this->canUpdateModel() ? $this->model->getAdminRoute() : null;
        return $route ? A::make()->attributes($this->attributes)->href($route) : '';
    }

    protected function canUpdateModel(): bool
    {
        $webuser = Yii::$app->getUser();

        if ($this->model instanceof Entry) {
            return $webuser->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $this->model]);
        }

        if ($this->model instanceof Section) {
            return $webuser->can(Section::AUTH_SECTION_UPDATE, ['section' => $this->model]);
        }

        $permissionName = $this->model->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        return $webuser->can($permissionName, ['asset' => $this->model]);
    }

    public static function tag(Asset|Entry|Section $model): string
    {
        return self::make()->model($model)->render();
    }
}
