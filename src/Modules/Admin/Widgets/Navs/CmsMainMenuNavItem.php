<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class CmsMainMenuNavItem extends NavItem
{
    use ModuleTrait;

    protected Module $module;

    public function __construct($config = [])
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $this->module = $module;

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon('book')
            ->label($this->module->getName())
            ->url(['/admin/entry/index'])
            ->roles([Category::AUTH_CATEGORY_UPDATE, Entry::AUTH_ENTRY_UPDATE]);

        $this->addEntrySubnavItems();
        $this->addCategorySubnavItems();

        parent::configure();
    }

    protected function addEntrySubnavItems(): void
    {
        if ($this->module->showEntryTypesInAside) {
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
        if (self::getModule()->enableCategories) {
            $this->addItem(NavItem::make()
                ->label(Yii::t('cms', 'Categories'))
                ->url(['/admin/category/index'])
                ->roles([Category::AUTH_CATEGORY_UPDATE])
                ->routes(['admin/category']));
        }
    }
}
