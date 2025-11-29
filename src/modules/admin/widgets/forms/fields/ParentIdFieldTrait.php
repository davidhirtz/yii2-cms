<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

trait ParentIdFieldTrait
{
    public string $indent = '–';
    public int|false $parentSlugMaxLength = 80;

    protected function getParentIdOptionDataValue(Category|Entry $model, ?string $language = null): string
    {
        return Yii::$app->getI18n()->callback($language, function () use ($model): string {
            $urlManager = Yii::$app->getUrlManager();
            $route = $model->getRoute();

            if (!$route) {
                return '';
            }

            $url = $model->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);
            $url = rtrim($url, '/') . '/';

            return Html::truncateText($url, $this->parentSlugMaxLength, '…/');
        });
    }
}
