<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\traits;

use Hirtz\Cms\models\Entry;
use Yii;

/**
 * @property bool|string $show_in_menu
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
        return (bool)$this->show_in_menu;
    }
}
