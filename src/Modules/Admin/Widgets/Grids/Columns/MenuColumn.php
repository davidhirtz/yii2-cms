<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;

/**
 * @template T of Entry
 * @extends LinkColumn<T>
 *
 * @property EntryGridView<Entry> $grid
 */
class MenuColumn extends LinkColumn
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->url ??= fn (Entry $model) => $model->getAdminRoute();
        $this->content ??= $this->getContent(...);

        parent::__construct($config);
    }

    #[Override]
    public function isVisible(): bool
    {
        if (parent::isVisible()) {
            foreach ($this->grid->provider->getModels() as $model) {
                if ($model->isMenuItem()) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getContent(Entry $entry): ?Stringable
    {
        $menus = $entry->getMenus();
        return $menus ? $this->getMenuIcon($menus) : null;
    }

    /**
     * @param array<int, Menu> $menus
     */
    protected function getMenuIcon(array $menus): Stringable
    {
        $names = array_map(fn (Menu $menu) => $menu->getName(), $menus);

        return Icon::make()
            ->name('stream')
            ->tooltip(implode(', ', $names));
    }
}
