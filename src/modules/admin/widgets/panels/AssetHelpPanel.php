<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\UpdateFileButtonTrait;

/**
 * @property Asset $model
 */
class AssetHelpPanel extends HelpPanel
{
    use UpdateFileButtonTrait;

    /**
     * @see AssetController::actionDuplicate()
     * @see AssetController::actionUpdate()
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getUpdateFileButton(),
            $this->getDuplicateButton(),
        ]);
    }
}
