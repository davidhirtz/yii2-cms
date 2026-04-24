<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
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
    protected int $parentCategoryBreadcrumbCount = 2;
    protected bool $showEntryCategories = true;
    protected bool $showEntrySections = true;

    private bool $isAsset = false;

    #[Override]
    protected function configure(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $this->module = $module;

        if ($this->model instanceof Asset) {
            $this->model($this->model->getParent());
            $this->isAsset = true;
        }

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

    protected function getEntryUpdateItem(): ?Stringable
    {
        return NavItem::make()
            ->icon('cog')
            ->label(Yii::t('skeleton', 'General'))
            ->routes(['admin/cms/entry/update', ...$this->additionalActiveRoutes['entry'] ?? []])
            ->url(['/admin/cms/entry/update', 'id' => $this->model->id]);
    }

    protected function getAssetsItem(): ?Stringable
    {
        return NavItem::make()
            ->badge($this->model->asset_count)
            ->icon('photo-film')
            ->label($this->model->getAttributeLabel('asset_count'))
            ->routes(['admin/cms/asset/', ...$this->additionalActiveRoutes['assets'] ?? []])
            ->url(['/admin/cms/asset/index', 'entry' => $this->model->id]);
    }

    public function getSubentriesItem(): ?Stringable
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('book')
            ->label(Yii::t('cms', 'Subentries'))
            ->routes(['admin/cms/entry/index', ...$this->additionalActiveRoutes['subentries'] ?? []])
            ->url(['/admin/cms/entry/index', 'parent' => $this->model->id])
            ->visible($this->model->hasDescendantsEnabled());
    }

    protected function getEntryCategoriesItem(): ?Stringable
    {
        return NavItem::make()
            ->badge($this->model->getCategoryCount())
            ->icon('folder-open')
            ->label(Yii::t('cms', 'Categories'))
            ->routes(['admin/cms/entry-category/'])
            ->url(['/admin/cms/entry-category/index', 'entry' => $this->model->id])
            ->visible($this->showEntryCategories);
    }


    protected function getEntrySectionsItem(): ?Stringable
    {
        return NavItem::make()
            ->label(Yii::t('cms', 'Sections'))
            ->url(['/admin/cms/section/index', 'entry' => $this->model->id])
            ->icon('th-list')
            ->badge($this->model->section_count)
            ->routes(['admin/cms/section/', ...$this->additionalActiveRoutes['sections'] ?? []])
            ->visible($this->showEntrySections);
    }


    protected function setCategoryBreadcrumbs(): void
    {
        $this->view->addBreadcrumb(Yii::t('cms', 'Categories'), ['/admin/cms/category/index']);

        if ($this->parentCategoryBreadcrumbCount > 0) {
            $categories = $this->model->ancestors;
            $count = count($categories);

            if ($count > $this->parentCategoryBreadcrumbCount) {
                $this->view->addBreadcrumb('…');
            }

            foreach ($categories as $category) {
                if (--$count < $this->parentCategoryBreadcrumbCount) {
                    $this->view->addBreadcrumb($category->getI18nAttribute('name'), $category->getAdminRoute());
                }
            }
            if (!$this->model->getIsNewRecord()) {
                $this->view->addBreadcrumb($this->model->getI18nAttribute('name'), $this->model->getAdminRoute());
            }
        }
    }

    protected function setAssetBreadcrumbs(): void
    {
        $route = $this->model->getAdminRoute() + ['#' => 'assets'];
        $this->view->addBreadcrumb(Yii::t('cms', 'Assets'), $route);
    }
}
