<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Helpers\FrontendLink;
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
    protected bool $showEntryTypes = false;
    protected bool $showModuleBreadcrumbs = true;
    protected bool $showUrl = true;

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
            $this->showEntryCategories = $this->model->hasCategoriesEnabled();
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $this->model->hasSectionsEnabled();
        }

        $this->addItem(
            $this->getEntryUpdateItem(),
            $this->getSubentriesItem(),
            $this->getEntryCategoriesItem(),
            $this->getEntrySectionsItem(),
        );


        parent::configure();
    }


    protected function getEntryUpdateItem(): ?Stringable
    {
        return NavItem::make()
            ->label($this->showEntryTypes ? $this->model->getTypeName() : Yii::t('cms', 'Entry'))
            ->url(['/admin/cms/entry/update', 'id' => $this->model->id])
            ->icon($this->model->getStatusIcon())
            ->routes(array_filter([
                'admin/cms/entry/update',
                'admin/cms/asset/' => ['entry'],
                !$this->isSection() ? 'admin/cms/asset/update' : null,
                ...$this->additionalActiveRoutes['entry'] ?? [],
            ]));
    }

    public function getSubentriesItem(): ?Stringable
    {
        return $this->model->hasDescendantsEnabled()
            ? NavItem::make()
                ->badge($this->model->entry_count)
                ->icon('book')
                ->label(Yii::t('cms', 'Subentries'))
                ->routes(['admin/cms/entry/index', ...$this->additionalActiveRoutes['subentries'] ?? []])
                ->url(['/admin/cms/entry/index', 'parent' => $this->model->id])
            : null;
    }

    protected function getEntryCategoriesItem(): ?Stringable
    {
        return Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CATEGORY_UPDATE, ['entry' => $this->model])
            ? NavItem::make()
                ->badge($this->model->getCategoryCount())
                ->icon('folder-open')
                ->label(Yii::t('cms', 'Categories'))
                ->routes(['admin/cms/entry-category/'])
                ->url(['/admin/cms/entry-category/index', 'entry' => $this->model->id])
                ->visible($this->showEntryCategories)
            : null;
    }


    protected function getEntrySectionsItem(): ?Stringable
    {
        return Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['entry' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('cms', 'Sections'))
                ->url(['/admin/cms/section/index', 'entry' => $this->model->id])
                ->icon('th-list')
                ->badge($this->model->section_count)
                ->routes([
                    'admin/cms/section/',
                    'admin/cms/asset/' => ['section'],
                    ...$this->isSection()
                        ? [
                            'admin/cms/asset/update',
                            'admin/cms/section-entry',
                        ]
                        : [],
                    ...$this->additionalActiveRoutes['sections'] ?? [],
                ])
                ->visible($this->showEntrySections)
            : null;
    }

    protected function setEntryBreadcrumbs(): void
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;

        if ($this->showEntryTypes) {
            if ($typeOptions = (Entry::instance()::getTypes()[$model->type] ?? null)) {
                $this->view->addBreadcrumb($typeOptions['plural'] ?? $typeOptions['name'], [
                    '/admin/cms/entry/index',
                    'type' => $model->type,
                ]);
            }
        }

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


    protected function getFrontendLink(): ?string
    {
        return $this->showUrl && $this->model ? FrontendLink::tag($this->model) : null;
    }

    protected function isSection(): bool
    {
        return $this->model instanceof Section;
    }

    protected function isAsset(): bool
    {
        return $this->isAsset;
    }
}
