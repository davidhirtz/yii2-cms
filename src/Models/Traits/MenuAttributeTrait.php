<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Yii;

/**
 * @property bool|string $show_in_menu
 * @mixin Entry
 */
trait MenuAttributeTrait
{
    public function getMenuAttributeRules(): array
    {
        return [
            [
                ['show_in_menu'],
                'boolean',
            ],
        ];
    }

    public function getMenuAttributeLabels(): array
    {
        return [
            'show_in_menu' => Yii::t('cms', 'MENU_ATTRIBUTE_SHOW_IN_MENU'),
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
