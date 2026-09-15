<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class CmsNavItem extends NavItem
{
    use ModuleTrait;

    protected bool $showEntryTypes = false;
    protected bool $showCategories = true;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->icon ??= 'book';
        $this->order ??= 10;
        $this->roles ??= [Category::AUTH_CATEGORY, Entry::AUTH_ENTRY];
        $this->url ??= ['/admin/cms/entry/index'];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->showCategories) {
            $this->showCategories = static::getModule()->enableCategories;
        }

        $this->label ??= Yii::t('cms', 'COMMON_ENTRIES');

        if ($this->showEntryTypes) {
            $this->addEntrySubnavItems();
        } else {
            $this->routes([
                'admin/cms/entry',
                'admin/cms/section',
                'admin/cms/entry-asset',
                'admin/cms/section-asset',
            ]);
        }

        if ($this->showCategories) {
            $this->addCategorySubnavItems();
        }

        parent::configure();
    }

    protected function addEntrySubnavItems(): void
    {
        $types = Entry::instance()::getTypeDefinitions();
        $currentType = $this->view->params['entryType'] ?? key($types);

        foreach ($types as $type => $definition) {
            $this->addItem(NavItem::make()
                ->active($currentType === $type))
                ->label($definition->getPlural())
                ->url(['/admin/cms/entry/index', 'type' => $type])
                ->roles([Entry::AUTH_ENTRY]);
        }
    }

    protected function addCategorySubnavItems(): void
    {
        $this->addItem(NavItem::make()
            ->label(Yii::t('cms', 'COMMON_CATEGORIES'))
            ->url(['/admin/cms/category/index'])
            ->roles([Category::AUTH_CATEGORY])
            ->routes(['admin/cms/category']));
    }
}
