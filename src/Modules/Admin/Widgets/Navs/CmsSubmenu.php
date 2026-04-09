<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Helpers\FrontendLink;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Navs\Traits\NavItemTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class CmsSubmenu extends Widget
{
    use ModuleTrait;
    use TitleTrait;
    use TagContentTrait;
    use UrlTrait;
    use NavItemTrait;
    use VisibilityTrait;

    protected Asset|Category|Entry|Section|null $model = null;
    protected Module $module;

    protected array $additionalActiveRoutes = [];
    protected int $parentCategoryBreadcrumbCount = 2;
    protected bool $showDefaultCategories = true;
    protected bool $showEntryCategories = true;
    protected bool $showEntrySections = true;
    protected bool $showEntryTypes = false;
    protected bool $showModuleBreadcrumbs = true;
    protected bool $showUrl = true;

    private bool $isAsset = false;

    public function model(Asset|Category|Entry|Section|null $model): static
    {
        $this->model = $model;
        return $this;
    }

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

        $model = $this->isSection() ? $this->model->entry : $this->model;
        $isEntry = $model instanceof Entry;

        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        $this->title ??= $isEntry ? $model->getI18nAttribute('name') : Yii::t('cms', 'Entries');

        if ($isEntry) {
            $this->url ??= $model->getAdminRoute();
        }

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $isEntry
                ? $model->hasCategoriesEnabled()
                : static::getModule()->enableCategories;
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $isEntry
                ? $model->hasSectionsEnabled()
                : static::getModule()->enableSections;
        }

        if ($isEntry) {
            $this->items = [...$this->items, ...$this->getEntryItems()];
        }

        $this->setBreadcrumbs();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Submenu::make()
            ->title($this->title)
            ->url($this->url)
            ->header(fn (Header $header) => $header
                ->content(...$this->content)
                ->subheading($this->renderFrontendLink()))
            ->items($this->items);
    }

    protected function getEntryItems(): array
    {
        return array_filter([
            ...$this->getEntryFormItems(),
            ...$this->getEntryParentItems(),
            ...$this->getEntryCategoryItems(),
            ...$this->getEntrySectionItems(),
        ]);
    }

    protected function getEntryFormItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        return [
            NavItem::make()
                ->label($this->showEntryTypes ? $entry->getTypeName() : Yii::t('cms', 'Entry'))
                ->url(['/admin/cms/entry/update', 'id' => $entry->id])
                ->icon($entry->getStatusIcon())
                ->routes(array_filter([
                    'admin/cms/entry/update',
                    'admin/cms/asset/' => ['entry'],
                    !$this->isSection() ? 'admin/cms/asset/update' : null,
                    ...$this->additionalActiveRoutes['entry'] ?? [],
                ])),
        ];
    }

    public function getEntryParentItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        if (!static::getModule()->enableEntryAssets || !$entry->hasDescendantsEnabled()) {
            return [];
        }

        return [
            NavItem::make()
                ->label(Yii::t('cms', 'Subentries'))
                ->url(['/admin/cms/entry/index', 'parent' => $entry->id])
                ->icon('book')
                ->badge($entry->entry_count)
                ->routes([
                    'admin/cms/entry/index',
                    ...$this->additionalActiveRoutes['subentries'] ?? [],
                ]),
        ];
    }

    protected function getEntryCategoryItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        if (
            !$this->showEntryCategories
            || !Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CATEGORY_UPDATE, ['entry' => $entry])
        ) {
            return [];
        }

        return [
            NavItem::make()
                ->label(Yii::t('cms', 'Categories'))
                ->url(['/admin/cms/entry-category/index', 'entry' => $entry->id])
                ->icon('folder-open')
                ->badge($entry->getCategoryCount())
                ->routes(['admin/cms/entry-category/']),
        ];
    }


    protected function getEntrySectionItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        if (
            !$this->showEntrySections
            || !Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])
        ) {
            return [];
        }

        return [
            NavItem::make()
                ->label(Yii::t('cms', 'Sections'))
                ->url(['/admin/cms/section/index', 'entry' => $entry->id])
                ->icon('th-list')
                ->badge($entry->section_count)
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
                ]),
        ];
    }

    protected function setBreadcrumbs(): void
    {
        if ($this->showModuleBreadcrumbs) {
            $this->setModuleBreadcrumbs();
        }

        if ($this->model instanceof Category) {
            $this->setCategoryBreadcrumbs();
        } elseif ($this->model) {
            $this->setEntryBreadcrumbs();
        }

        if ($this->isAsset()) {
            $this->setAssetBreadcrumbs();
        }
    }

    protected function setModuleBreadcrumbs(): void
    {
        $this->view->addBreadcrumb(Yii::t('cms', 'Entries'), [
            '/admin/cms/entry/index',
            'type' => static::getModule()->defaultEntryType,
        ]);
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

        if ($model->parent_id) {
            $isIndex = Yii::$app->requestedRoute === 'admin/cms/entry/index';

            foreach ($model->ancestors as $ancestor) {
                $this->view->addBreadcrumb($ancestor->getI18nAttribute('name'), $isIndex
                    ? ['index', 'parent' => $ancestor->id]
                    : $ancestor->getAdminRoute());
            }
        }

        $this->setEntryBreadcrumb();

        if ($this->isSection()) {
            $this->view->addBreadcrumb(Yii::t('cms', 'Section'), $this->model->getAdminRoute());
        }
    }

    protected function setEntryBreadcrumb(): void
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;

        $this->view->addBreadcrumb($this->showEntryTypes
            ? $model->getTypeName()
            : Yii::t('cms', 'Entry'), $model->getAdminRoute());
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


    protected function renderFrontendLink(): ?string
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
