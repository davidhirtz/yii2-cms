<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxListField;
use Override;
use Yii;

/**
 * @property Entry|null $model
 */
class MenuIdsField extends CheckboxListField
{
    public ?string $property = 'menu_ids';

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->items !== [];
    }

    #[Override]
    protected function configure(): void
    {
        if (!$this->items && $this->model instanceof Entry) {
            foreach ($this->model->getAvailableMenus() as $menu) {
                $this->items[$menu->value] = $menu->getName();
                $this->addAncestorWarning($menu);
            }
        }

        parent::configure();
    }

    /**
     * A menu item whose ancestor is not in the same menu is never reached through it, which is invisible in a
     * checkbox on its own.
     */
    protected function addAncestorWarning(Menu $menu): void
    {
        if (!$this->model?->parent_id) {
            return;
        }

        foreach ($this->model->ancestors as $ancestor) {
            if (!$ancestor->isMenuItem($menu->value)) {
                $this->itemAttributes[$menu->value] ??= [
                    'class' => 'text-invalid',
                    'data-tooltip' => '',
                    'title' => Yii::t('cms', 'ENTRY_MENU_IDS_PARENT_ENTRY', [
                        'entry' => $ancestor->getI18nAttribute('name'),
                    ]),
                ];

                return;
            }
        }
    }
}
