<?php

namespace davidhirtz\yii2\cms\models\traits;

trait AssetParentTrait
{
    use VisibleAttributeTrait;

    public function getSrcsetSizes(): ?string
    {
        return $this->getTypeOptions()['sizes'] ?? null;
    }

    public function getTransformationNames(): array
    {
        return $this->getTypeOptions()['transformations'] ?? [];
    }

    public function getVisibleAssets(): array
    {
        return $this->isAttributeVisible('#assets') ? $this->assets : [];
    }
}