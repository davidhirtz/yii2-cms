<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

trait ParentIdSelectFieldTrait
{
    protected string $indent = '–';

    public function indent(string $indent): static
    {
        $this->indent = $indent;
        return $this;
    }
}
