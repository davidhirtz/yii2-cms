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

        $this->label ??= $this->showCategories || $this->showEntryTypes
            ? Yii::t('cms', 'Contents')
            : Yii::t('cms', 'Entries');

        $this->addEntrySubnavItems();

        if ($this->showCategories) {
            $this->addCategorySubnavItems();
        }

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
                    ->url(['/admin/cms/entry/index', 'type' => $type])
                    ->roles([Entry::AUTH_ENTRY_UPDATE]);
            }
        } else {
            $this->addItem(NavItem::make()
                ->label(Yii::t('cms', 'Entries'))
                ->url(['/admin/cms/entry/index'])
                ->roles([Entry::AUTH_ENTRY_UPDATE])
                ->routes(['admin/cms/entry', 'admin/cms/section']));
        }
    }

    protected function addCategorySubnavItems(): void
    {
        $this->addItem(NavItem::make()
            ->label(Yii::t('cms', 'Categories'))
            ->url(['/admin/cms/category/index'])
            ->roles([Category::AUTH_CATEGORY_UPDATE])
            ->routes(['admin/cms/category']));
    }
}
