<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Widgets\NavItems;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;

/**
 * @property EntryGridView $grid
 */
class MenuColumn extends LinkColumn
{
    public function __construct()
    {
        if ($this->isVisible()) {
            $visible = false;

            foreach ($this->grid->provider->getModels() as $model) {
                if ($this->getIsMenuItem($model)) {
                    $visible = true;
                    break;
                }
            }

            $this->visible($visible);
        }

        $this->url ??= fn (Entry $model) => $model->getAdminRoute();
        $this->content ??= $this->getContent(...);

        parent::__construct();
    }

    protected function getContent(Entry $entry): ?Stringable
    {
        return $this->getIsMenuItem($entry)
            ? $this->getMenuIcon($entry)
            : null;
    }

    protected function getMenuIcon(Entry $entry): Stringable
    {
        return Icon::make()
            ->name('stream')
            ->tooltip($entry->getAttributeLabel('show_in_menu'));
    }

    protected function getIsMenuItem(Entry $entry): bool
    {
        return NavItems::getIsMenuItem($entry);
    }
}
