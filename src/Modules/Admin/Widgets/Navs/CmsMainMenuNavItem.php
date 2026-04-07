<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class CmsMainMenuNavItem extends NavItem
{
    use ModuleTrait;

    protected bool $showEntryTypes = false;

    public function showEntryTypes(bool $showEntryTypes): static
    {
        $this->showEntryTypes = $showEntryTypes;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= Icon::make()->name('book');
        $this->roles ??= [Category::AUTH_CATEGORY_UPDATE, Entry::AUTH_ENTRY_UPDATE];

        $this->addEntrySubnavItems();
        $this->addCategorySubnavItems();

        parent::configure();
    }

    protected function addEntrySubnavItems(): void
    {
        if ($this->showEntryTypes) {
            $typeOptions = Entry::instance()::getTypes();
            $currentType = $this->view->params['entryType'] ?? key($typeOptions);

            foreach ($typeOptions as $type => $attributes) {
                $this->addItem(NavItem::make()
                    ->active($currentType === $type))
                    ->label($attributes['label'] ?? $attributes['plural'] ?? $attributes['name'])
                    ->url(['/admin/entry/index', 'type' => $type])
                    ->roles([Entry::AUTH_ENTRY_UPDATE]);
            }
        } else {
            $this->addItem(NavItem::make()
                ->label(Yii::t('cms', 'Entries'))
                ->url(['/admin/entry/index'])
                ->roles([Entry::AUTH_ENTRY_UPDATE])
                ->routes(['admin/entry', 'admin/entry-category', 'admin/section', 'cms/']));
        }
    }

    protected function addCategorySubnavItems(): void
    {
        if (static::getModule()->enableCategories) {
            $this->addItem(NavItem::make()
                ->label(Yii::t('cms', 'Categories'))
                ->url(['/admin/category/index'])
                ->roles([Category::AUTH_CATEGORY_UPDATE])
                ->routes(['admin/category']));
        }
    }
}
