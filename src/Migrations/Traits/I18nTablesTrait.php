<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations\Traits;

use Hirtz\Cms\modules\ModuleTrait;
use Yii;

trait I18nTablesTrait
{
    use ModuleTrait;

    protected function i18nTablesCallback(callable $callback): void
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->getI18n()->callback($language, $callback);
        }
    }

    protected function getLanguages(): array
    {
        return static::getModule()->getLanguages();
    }
}
