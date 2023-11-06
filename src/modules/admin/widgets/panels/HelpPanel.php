<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\ActiveRecord;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\DuplicateButtonTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\LinkButtonTrait;

abstract class HelpPanel extends \davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel
{
    use DuplicateButtonTrait;
    use LinkButtonTrait;

    public ?ActiveRecord $model = null;

    public function init(): void
    {
        $this->content ??= $this->renderButtonToolbar($this->getButtons());

        parent::init();
    }

    abstract protected function getButtons(): array;
}