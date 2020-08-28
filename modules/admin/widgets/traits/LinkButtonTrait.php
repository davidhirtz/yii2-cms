<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Trait LinkButtonTrait
 */
trait LinkButtonTrait
{
    /**
     * @return string|null
     */
    protected function getLinkButton()
    {
        if (!$this->model->isDisabled()) {
            if ($route = $this->model->getRoute()) {
                $manager = Yii::$app->getUrlManager();
                $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

                if ($url) {
                    return Html::a(Html::iconText('external-link-alt', Yii::t('cms', 'Open website')), $url, [
                        'class' => 'btn btn-secondary',
                        'target' => 'blank',
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isDraft(): bool
    {
        return $this->model->isDraft();
    }
}