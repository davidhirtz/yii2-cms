<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\forms\traits;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\Entry;
use Yii;

trait ParentIdSelectFieldTrait
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

            if (false !== $this->parentSlugMaxLength && mb_strlen($url) > $this->parentSlugMaxLength) {
                $url = mb_substr($url, 0, $this->parentSlugMaxLength - 4) . '…/';
            }

            return $url;
        });
    }
}
