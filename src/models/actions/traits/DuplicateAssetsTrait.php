<?php

namespace davidhirtz\yii2\cms\models\actions\traits;

use davidhirtz\yii2\cms\models\actions\DuplicateAsset;
use davidhirtz\yii2\cms\models\Asset;
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