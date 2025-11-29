<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;

trait SlugFieldTrait
{
    protected function getSlugField(): ?Stringable
    {
        return $this->hasSlugField()
            ? InputField::make()
                ->property('slug')
                ->prepare(function (InputField $field) {
                    $field->prepend(Div::make()
                        ->class('text-truncate')
                        ->attribute('id', $this->getSlugId($field->language))
                        ->text($this->getSlugBaseUrl($field->language)));
                })
            : null;
    }

    protected function hasSlugField(): bool
    {
        return true;
    }

    protected function getSlugId(?string $language = null): string
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }
}