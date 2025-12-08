<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\UpdateFileButtonTrait;

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
