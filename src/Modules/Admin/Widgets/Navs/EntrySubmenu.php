<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
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

    protected array $additionalActiveRoutes = [];

    /**
     * @param array<string, list<string>> $additionalActiveRoutes keyed by the item the routes belong to
     */
    public function additionalActiveRoutes(array $additionalActiveRoutes): static
    {
        $this->additionalActiveRoutes = $additionalActiveRoutes;
        return $this;
    }
    protected bool $showEntryCategories = true;
    protected bool $showEntrySections = true;

    #[Override]
    protected function configure(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $this->module = $module;

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $this->model->hasCategoriesEnabled()
                && $this->webuser->can(Entry::AUTH_ENTRY_CATEGORY_UPDATE, ['entry' => $this->model]);
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $this->model->hasSectionsEnabled()
                && $this->webuser->can(Section::AUTH_SECTION_UPDATE, ['entry' => $this->model]);
        }

        $this->addItem(
            $this->getEntryUpdateItem(),
            $this->getAssetsItem(),
            $this->getSubentriesItem(),
            $this->getEntryCategoriesItem(),
            $this->getEntrySectionsItem(),
        );


        parent::configure();
    }

    protected function getEntryUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label(Yii::t('skeleton', 'COMMON_GENERAL'))
            ->routes(['admin/cms/entry/update', ...$this->additionalActiveRoutes['entry'] ?? []])
            ->url($this->model->getAdminRoute());
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->routes(
                [
                    'admin/cms/entry-asset',
                    ...$this->additionalActiveRoutes['assets'] ?? [],
                ]
            )
            ->url(EntryAsset::getAdminIndexRoute($this->model));
    }

    public function getSubentriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('book')
            ->label(Yii::t('cms', 'COMMON_SUBENTRIES'))
            ->routes(['admin/cms/entry/index', ...$this->additionalActiveRoutes['subentries'] ?? []])
            ->url(['/admin/cms/entry/index', 'parent' => $this->model->id])
            ->visible($this->model->hasDescendantsEnabled());
    }

    protected function getEntryCategoriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->getCategoryCount())
            ->icon('folder-open')
            ->label(Yii::t('cms', 'COMMON_CATEGORIES'))
            ->routes(['admin/cms/entry-category/'])
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
            ->routes(['admin/cms/section/', ...$this->additionalActiveRoutes['sections'] ?? []])
            ->visible($this->showEntrySections);
    }
}
