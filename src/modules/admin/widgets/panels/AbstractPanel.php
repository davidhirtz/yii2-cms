<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\panels;

use Hirtz\Cms\models\ActiveRecord;
use Hirtz\Cms\modules\admin\widgets\panels\traits\LinkButtonTrait;
use Hirtz\Media\modules\admin\widgets\panels\traits\DuplicateButtonTrait;
use Hirtz\Skeleton\widgets\panels\Panel;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

abstract class AbstractPanel extends Widget
{
    use DuplicateButtonTrait;
    use ModelWidgetTrait;
    use LinkButtonTrait;

    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->attribute('id', 'operations')
            ->buttons(...$this->getButtons());
    }

    abstract protected function getButtons(): array;
}
