<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetSubmenuItem;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class EntrySubmenu extends Submenu
{
    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;
    use ModuleTrait;

    protected Module $module;
    protected bool $showEntryCategories = true;
    protected bool $showEntrySections = true;

    #[Override]
    protected function configure(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $this->module = $module;

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $this->model->allowsCategories()
                && $this->webuser->can(Entry::AUTH_ENTRY);
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $this->model->allowsSections()
                && $this->webuser->can(Entry::AUTH_ENTRY);
        }

        $this->addItem(
            entry: $this->getEntryUpdateItem(),
            assets: $this->getAssetsItem(),
            subentries: $this->getSubentriesItem(),
            categories: $this->getEntryCategoriesItem(),
            sections: $this->getEntrySectionsItem(),
        );

        $this->backUrl ??= $this->getAdminBackUrl($this->model);

        parent::configure();
    }

    protected function getEntryUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label($this->model->getAdminType())
            ->addRoute('admin/cms/entry/update')
            ->url($this->model->getAdminRoute() ?: null);
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->addRoute('admin/cms/entry-asset')
            ->url(EntryAsset::getAdminIndexRoute($this->model))
            ->visible($this->model->allowsAssets());
    }

    public function getSubentriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('book')
            ->label(Yii::t('cms', 'COMMON_SUBENTRIES'))
            ->addRoute('admin/cms/entry/index')
            ->url(['/admin/cms/entry/index', 'parent' => $this->model->id])
            ->visible($this->model->allowsDescendants());
    }

    protected function getEntryCategoriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->getCategoryCount())
            ->icon('folder-open')
            ->label(Yii::t('cms', 'COMMON_CATEGORIES'))
            ->addRoute('admin/cms/entry-category/')
            ->url(['/admin/cms/entry-category/index', 'entry' => $this->model->id])
            ->visible($this->showEntryCategories);
    }

    protected function getEntrySectionsItem(): ?NavItem
    {
        return NavItem::make()
            ->label(Yii::t('cms', 'COMMON_SECTIONS'))
            ->url(['/admin/cms/section/index', 'entry' => $this->model->id])
            ->icon('th-list')
            ->badge($this->model->section_count)
            ->addRoute('admin/cms/section/')
            ->visible($this->showEntrySections);
    }
}
