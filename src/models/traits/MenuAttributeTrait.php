<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Entry;
use Yii;

/**
 * @property bool $show_in_menu
 * @mixin Entry
 */
trait MenuAttributeTrait
{
    public function getMenuAttributeTraitRules(): array
    {
        return [
            [
                ['show_in_menu'],
                'boolean',
            ],
        ];
    }

    public function getMenuAttributeTraitAttributeLabels(): array
    {
        return [
            'show_in_menu' => Yii::t('cms', 'Show in menu'),
        ];
    }

    public function hasShowInMenuEnabled(): bool
    {
        return $this->getTypeOptions()['hasShowInMenuEnabled'] ?? true;
    }

    public function isMenuItem(): bool
    {
        return $this->show_in_menu;
    }
}
