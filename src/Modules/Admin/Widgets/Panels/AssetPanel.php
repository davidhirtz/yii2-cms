<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\UpdateFileButtonTrait;

/**
 * @template T of Asset
 * @extends AbstractPanel<T>
 */
class AssetPanel extends AbstractPanel
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
