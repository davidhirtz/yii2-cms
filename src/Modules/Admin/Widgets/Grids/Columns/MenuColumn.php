<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Widgets\NavItems;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;

/**
 * @property EntryGridView $grid
 */
class MenuColumn extends LinkColumn
{
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
                if ($this->getIsMenuItem($model)) {
                    return true;
                }
            }
        }

        return false;
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
