<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\panels;

use Hirtz\Cms\models\Asset;
use Hirtz\Cms\modules\admin\controllers\AssetController;
use Hirtz\Cms\modules\admin\widgets\panels\traits\UpdateFileButtonTrait;

/**
 * @property Asset $model
 */
class AssetHelpPanel extends AbstractPanel
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
