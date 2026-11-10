<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Stringable;

trait SlugFieldTrait
{
    protected function getSlugField(): ?Stringable
    {
        return $this->hasSlugField()
            ? InputField::make()
                ->property('slug')
                ->prepare(function (InputField $field): void {
                    $url = $this->getSlugBaseUrl($field->getLanguage());

                    $field->prepend(Div::make()
                        ->attribute('title', $url)
                        ->class('text-truncate hidden sm:block')
                        ->addStyle(['max-width' => 'min(24rem, 40vw)'])
                        ->text($this->formatSlugBaseUrl($url)));
                })
            : null;
    }

    abstract protected function getSlugBaseUrl(string $language): string;

    protected function formatSlugBaseUrl(string $url): string
    {
        return $url;
    }

    protected function hasSlugField(): bool
    {
        return true;
    }
}
