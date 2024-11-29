<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

trait LinkButtonTrait
{
    protected function getLinkButton(array $options = []): string
    {
        if (!$this->model->isDisabled()) {
            if ($route = $this->model->getRoute()) {
                $manager = Yii::$app->getUrlManager();
                $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

                if ($url) {
                    return Html::a(Html::iconText('external-link-alt', Yii::t('cms', 'Open website')), $url, [
                        'class' => 'btn btn-secondary',
                        'target' => 'blank',
                        ...$options,
                    ]);
                }
            }
        }

        return '';
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft();
    }
}
