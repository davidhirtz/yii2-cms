<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\UpdateFileButtonTrait;

/**
 * @property Asset $model
 */
class AssetHelpPanel extends HelpPanel
{
    use UpdateFileButtonTrait;

    protected function getButtons(): array
    {
        return array_filter([
            $this->getUpdateFileButton(),
        ]);
    }
}