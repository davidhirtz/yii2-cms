<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\navs;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\helpers\FrontendLink;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\navs\NavItem;
use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use Override;
use Yii;

/**
 * @property Asset|Category|Entry|Section|null $model
 */
class CmsSubmenu extends Submenu
{
    use ModuleTrait;
    use ModelWidgetTrait;

    /**
     * @var bool whether default categories should be shown as nav items
     */
    public bool $showDefaultCategories = true;

    /**
     * @var int the number of parent categories should be shown in the breadcrumb. Set to `0` to disable parent
     * category breadcrumbs.
     */
    public int $parentCategoryBreadcrumbCount = 2;

    /**
     * @var bool whether entry types should be listed as items
     */
    public bool $showEntryTypes = false;

    /**
     * @var bool whether entry categories should be visible
     */
    public bool $showEntryCategories = true;

    /**
     * @var bool whether entry sections should be visible
     */
    public bool $showEntrySections = true;

    /**
     * @var bool whether to show the admin module in the breadcrumbs
     */
    public bool $showModuleBreadcrumbs = true;

    /**
     * @var array<string, array> additional active routes, indexed by the item name
     */
    public array $additionalActiveRoutes = [];

    /**
     * @var bool whether the website url to given model should be displayed
     */
    public bool $showUrl = true;

    protected Module $module;
    protected bool $isAsset = false;

    public function __construct()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $this->module = $module;

        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model instanceof Asset) {
            $this->model($this->model->getParent());
            $this->isAsset = true;
        }

        $model = $this->isSection() ? $this->model->entry : $this->model;
        $isEntry = $model instanceof Entry;

        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        $this->title ??= $isEntry ? $model->getI18nAttribute('name') : $this->module->getName();
        $this->url ??= $isEntry ? ['/admin/entry/update', 'id' => $model->id] : $this->module->url;

        if ($this->title && $this->showUrl) {
            $this->content($this->getUrl());
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

        $this->items = [...$this->items, ...$isEntry ? $this->getEntryItems() : $this->getDefaultItems()];

        $this->setBreadcrumbs();
        parent::configure();
    }

    protected function getDefaultItems(): array
    {
        return array_filter([...$this->getEntryGridViewItems(), ...$this->getCategoryGridViewItems()]);
    }

    protected function getEntryGridViewItems(): array
    {
        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE)) {
            return [];
        }

        if ($this->showEntryTypes) {
            $items = [];

            foreach (Entry::instance()::getTypes() as $type => $attributes) {
                $items[] = NavItem::make()
                    ->label($attributes['plural'] ?? $attributes['name'])
                    ->url(['/admin/entry/index', 'type' => $type])
                    ->icon($attributes['icon'] ?? 'book')
                    ->routes(['admin/entry' => ['type' => $type]]);
            }

            return $items;
        }

        return [
            NavItem::make()
                ->label(Yii::t('cms', 'Entries'))
                ->url(['/admin/entry/index'])
                ->icon('book')
                ->routes([
                    'admin/entry/',
                    ...$this->additionalActiveRoutes['entries'] ?? [],
                ]),
        ];
    }

    protected function getCategoryGridViewItems(): array
    {
        if (!$this->showDefaultCategories || !Yii::$app->getUser()->can(Category::AUTH_CATEGORY_UPDATE)) {
            return [];
        }

        return [
            NavItem::make()
                ->label(Yii::t('cms', 'Categories'))
                ->url(['/admin/category/index'])
                ->icon('folder-open')
                ->routes([
                    'admin/category/',
                    ...$this->additionalActiveRoutes['categories'] ?? [],
                ]),
        ];
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
                ->url(['/admin/entry/update', 'id' => $entry->id])
                ->icon($entry->getStatusIcon())
                ->routes([
                    'admin/entry/update',
                    'admin/cms/asset/' => ['entry'],
                    !$this->isSection() ? 'admin/cms/asset/update' : null,
                    ...$this->additionalActiveRoutes['entry'] ?? [],
                ]),
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
                ->url(['/admin/entry/index', 'parent' => $entry->id])
                ->icon('book')
                ->badge($entry->entry_count)
                ->routes([
                    'admin/entry/index',
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
                ->url(['/admin/entry-category/index', 'entry' => $entry->id])
                ->icon('folder-open')
                ->badge($entry->getCategoryCount())
                ->routes(['admin/entry-category/']),
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
                ->url(['/admin/section/index', 'entry' => $entry->id])
                ->icon('th-list')
                ->badge($entry->section_count)
                ->routes([
                    'admin/section/',
                    'admin/cms/asset/' => ['section'],
                    ...$this->isSection()
                        ? [
                            'admin/cms/asset/update',
                            'admin/section-entry',
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
        $this->view->addBreadcrumb($this->module->getName(), [
            '/admin/entry/index',
            'type' => static::getModule()->defaultEntryType,
        ]);
    }

    protected function setEntryBreadcrumbs(): void
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;

        if ($this->showEntryTypes) {
            if ($typeOptions = (Entry::instance()::getTypes()[$model->type] ?? null)) {
                $this->view->addBreadcrumb($typeOptions['plural'] ?? $typeOptions['name'], [
                    '/admin/entry/index',
                    'type' => $model->type,
                ]);
            }
        }

        if ($model->parent_id) {
            $isIndex = Yii::$app->requestedRoute === 'admin/entry/index';

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
        $this->view->addBreadcrumb(Yii::t('cms', 'Categories'), ['/admin/category/index']);

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


    protected function getUrl(): string
    {
        $link = $this->model ? FrontendLink::tag($this->model) : null;
        return $link ? Html::tag('div', $link, ['class' => 'small']) : '';
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
