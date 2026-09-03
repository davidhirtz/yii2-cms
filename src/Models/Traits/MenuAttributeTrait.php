<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Entry;
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
            'show_in_menu' => Lang::t('cms', 'MENU_ATTRIBUTE_SHOW_IN_MENU'),
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
