<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions\Traits;

use Hirtz\Cms\Models\Actions\DuplicateAsset;
use Hirtz\Cms\Models\Asset;
use Yii;

trait DuplicateAssetsTrait
{
    protected function duplicateAssets(): void
    {
        Yii::debug('Duplicating assets ...');

        $assets = $this->getAssets();
        $position = 0;

        foreach ($assets as $asset) {
            DuplicateAsset::create([
                'asset' => $asset,
                'parent' => $this->duplicate,
                'shouldUpdateParentAfterInsert' => false,
                'attributes' => [
                    'status' => $asset->status,
                    'position' => ++$position,
                ],
            ]);
        }
    }

    /**
     * @return Asset[]
     */
    protected function getAssets(): array
    {
        return $this->model->getAssets()->all();
    }
}
