<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Stringable;

trait LinkButtonTrait
{
    protected function getLinkButton(): ?Stringable
    {
        if ($this->model->isDisabled()) {
            return null;
        }

        $url = $this->isDraft() ? $this->model->getDraftUrl() : $this->model->getUrl(true);

        if (!$url) {
            return null;
        }

        return Button::make()
            ->primary()
            ->text(Lang::t('cms', 'COMMON_OPEN_WEBSITE'))
            ->icon('external-link-alt')
            ->url($url)
            ->target('_blank');
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft();
    }
}
