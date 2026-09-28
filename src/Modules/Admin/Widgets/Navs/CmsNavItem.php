<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class CmsNavItem extends NavItem
{
    use ModuleTrait;

    public bool $showEntryTypes = false;
    public bool $showCategories = true;
    public bool $showBlocks = true;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->icon ??= 'book';
        $this->order ??= 10;
        $this->roles ??= [Block::AUTH_BLOCK, Category::AUTH_CATEGORY, Entry::AUTH_ENTRY];
        $this->url ??= ['/admin/cms/entry/index'];

        parent::__construct($config);
    }

    public function showEntryTypes(bool $showEntryTypes = true): static
    {
        $this->showEntryTypes = $showEntryTypes;
        return $this;
    }

    public function showCategories(bool $showCategories = true): static
    {
        $this->showCategories = $showCategories;
        return $this;
    }

    public function showBlocks(bool $showBlocks = true): static
    {
        $this->showBlocks = $showBlocks;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->showCategories) {
            $this->showCategories = static::getModule()->enableCategories;
        }

        if ($this->showBlocks) {
            $this->showBlocks = static::getModule()->enableBlocks;
        }

        $this->label ??= Yii::t('cms', 'COMMON_ENTRIES');

        $this->routes([
            'admin/cms/entry',
            'admin/cms/section',
            'admin/cms/entry-asset',
            'admin/cms/section-asset',
        ]);

        if ($this->showEntryTypes) {
            $this->addEntrySubnavItems();
        }

        if ($this->showCategories) {
            $this->addCategorySubnavItems();
        }

        if ($this->showBlocks) {
            $this->addBlockSubnavItems();
        }

        parent::configure();
    }

    /**
     * The current type is the one `EntryHeader`, `EntrySubmenu` or `SectionSubmenu` published while the page
     * rendered, which happens before the layout renders this. A page outside the entries has none.
     */
    protected function addEntrySubnavItems(): void
    {
        $currentType = $this->view->params['entryType'] ?? null;

        foreach (Entry::instance()::getTypeDefinitions() as $type => $definition) {
            $this->addItem(NavItem::make()
                ->active($currentType === $type)
                ->label($definition->getPlural())
                ->url(['/admin/cms/entry/index', 'type' => $type])
                ->roles([Entry::AUTH_ENTRY]));
        }
    }

    protected function addBlockSubnavItems(): void
    {
        $this->addItem(NavItem::make()
            ->label(Yii::t('cms', 'COMMON_BLOCKS'))
            ->url(['/admin/cms/block/index'])
            ->roles([Block::AUTH_BLOCK])
            ->routes(['admin/cms/block', 'admin/cms/block-asset', 'admin/cms/block-entry']));
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
