<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\ActiveRecord;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\LinkButtonTrait;
use davidhirtz\yii2\media\modules\admin\widgets\panels\traits\DuplicateButtonTrait;
use davidhirtz\yii2\skeleton\widgets\panels\Panel;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
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
