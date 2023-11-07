<?php

namespace davidhirtz\yii2\cms\models\traits;

trait VisibleAttributeTrait
{
    public function getVisibleAttribute(string $attribute): mixed
    {
        return $this->isAttributeVisible($attribute) ? $this->getI18nAttribute($attribute) : false;
    }

    public function isAttributeVisible(string $attribute): bool
    {
        $typeOptions = $this->getTypeOptions()['hiddenFields'] ?? [];
        return $typeOptions !== ['*'] && !in_array($attribute, $typeOptions);
    }
}