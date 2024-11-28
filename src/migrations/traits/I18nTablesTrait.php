<?php

namespace davidhirtz\yii2\cms\migrations\traits;

use davidhirtz\yii2\cms\modules\ModuleTrait;
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
