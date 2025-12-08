<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\forms\traits;

use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\widgets\forms\fields\InputField;
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
                        ->attribute('id', $this->getSlugId($field->language))
                        ->class('text-truncate hidden sm:block')
                        ->addStyle(['max-width' => 'min(24rem, 40vw)'])
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
