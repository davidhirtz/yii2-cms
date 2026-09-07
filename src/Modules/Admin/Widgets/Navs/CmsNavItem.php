<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;

class CmsNavItem extends NavItem
{
    use ModuleTrait;

    protected bool $showEntryTypes = false;
    protected bool $showCategories = true;

    public function __construct(array $config = [])
    {
        $this->icon ??= 'book';
        $this->order ??= 10;
        $this->roles ??= [Category::AUTH_CATEGORY_UPDATE, Entry::AUTH_ENTRY_UPDATE];
        $this->url ??= ['/admin/cms/entry/index'];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->showCategories) {
            $this->showCategories = static::getModule()->enableCategories;
        }

        $this->label ??= Lang::t('cms', 'COMMON_ENTRIES');

        if ($this->showEntryTypes) {
            $this->addEntrySubnavItems();
        } else {
            $this->routes([
                'admin/cms/entry',
                'admin/cms/section',
                'admin/cms/asset',
            ]);
        }

        if ($this->showCategories) {
            $this->addCategorySubnavItems();
        }

        parent::configure();
    }

    protected function addEntrySubnavItems(): void
    {
        $typeOptions = Entry::instance()::getTypes();
        $currentType = $this->view->params['entryType'] ?? key($typeOptions);

        foreach ($typeOptions as $type => $attributes) {
            $this->addItem(NavItem::make()
                ->active($currentType === $type))
                ->label($attributes['label'] ?? $attributes['plural'] ?? $attributes['name'])
                ->url(['/admin/cms/entry/index', 'type' => $type])
                ->roles([Entry::AUTH_ENTRY_UPDATE]);
        }
    }

    protected function addCategorySubnavItems(): void
    {
        $this->addItem(NavItem::make()
            ->label(Lang::t('cms', 'COMMON_CATEGORIES'))
            ->url(['/admin/cms/category/index'])
            ->roles([Category::AUTH_CATEGORY_UPDATE])
            ->routes(['admin/cms/category']));
    }
}
