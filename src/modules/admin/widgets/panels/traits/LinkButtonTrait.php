<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\traits;

use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
use Yii;

trait LinkButtonTrait
{
    protected function getLinkButton(): ?Stringable
    {
        if ($this->model->isDisabled()) {
            return null;
        }

        $route = $this->model->getRoute();

        if (!$route) {
            return null;
        }

        $manager = Yii::$app->getUrlManager();
        $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

        return Button::make()
            ->secondary()
            ->text(Yii::t('cms', 'Open website'))
            ->icon('external-link-alt')
            ->href($url)
            ->target('_blank');
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft();
    }
}
