<?php

namespace davidhirtz\yii2\cms\models\traits;

trait AssetParentTrait
{
    public function getVisibleAssets(): array
    {
        return $this->isAttributeVisible('#assets') ? $this->assets : [];
    }
}