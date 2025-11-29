<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;
use Yii;

trait SlugFieldTrait
{
    protected function getSlugField(): ?Stringable
    {
        return InputField::make()
            ->property('slug')
            ->prepare(function (InputField $field) {
                $field->prepend(Div::make()
                    ->class('text-truncate')
                    ->attribute('id', $this->getSlugId($field->language))
                    ->text($this->getSlugBaseUrl($field->language)));
            });
    }

//    protected function getSlugBaseUrl(?string $language = null): string
//    {
//        $manager = Yii::$app->getUrlManager();
//        $url = $manager->createAbsoluteUrl(['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null]);
//        return rtrim($url, '/') . '/';
//    }

    protected  function getSlugId(?string $language = null): string
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }
}