<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\navs;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

class Submenu extends \davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu
{
    use ModuleTrait;

    public Asset|Category|Entry|Section|null $model = null;

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
     * @var bool whether the website url to given model should be displayed
     */
    public bool $showUrl = true;

    private ?Module $_parentModule = null;
    private bool $_isAsset = false;

    public function init(): void
    {
        if ($this->model instanceof Asset) {
            $this->model = $this->model->getParent();
            $this->_isAsset = true;
        }

        $model = $this->isSection() ? $this->model->entry : $this->model;
        $isEntry = $model instanceof Entry;

        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        if (!$this->title) {
            $this->title = $isEntry ? Html::a($model->getI18nAttribute('name'), ['/admin/entry/update', 'id' => $model->id]) :
                Html::a($this->getParentModule()->name, $this->getParentModule()->url);
        }

        if ($this->title && $this->showUrl) {
            $this->title .= $this->getUrl();
        }

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $isEntry ? $model->hasCategoriesEnabled() : static::getModule()->enableCategories;
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $isEntry ? $model->hasSectionsEnabled() : static::getModule()->enableSections;
        }

        $this->items = array_merge($this->items, $isEntry ? $this->getEntryItems() : $this->getDefaultItems());

        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->setBreadcrumbs();
        return parent::run();
    }

    /**
     * @return array
     */
    protected function getDefaultItems(): array
    {
        return array_filter(array_merge($this->getEntryGridViewItems(), $this->getCategoryGridViewItems()));
    }

    /**
     * @return array
     */
    protected function getEntryGridViewItems(): array
    {
        $canEntryUpdate = Yii::$app->getUser()->can('entryUpdate');
        $items = [];

        if ($this->showEntryTypes) {
            foreach (Entry::getTypes() as $type => $attributes) {
                $items[] = [
                    'label' => $attributes['plural'] ?? $attributes['name'],
                    'url' => ['/admin/entry/index', 'type' => $type],
                    'visible' => $canEntryUpdate,
                    'active' => ['admin/entry' => ['type' => $type]],
                    'icon' => $attributes['icon'] ?? 'book',
                    'labelOptions' => [
                        'class' => 'd-none d-md-inline'
                    ],
                ];
            }
        } elseif ($this->showDefaultCategories) {
            $items[] = [
                'label' => Yii::t('cms', 'Entries'),
                'url' => ['/admin/entry/index'],
                'visible' => $canEntryUpdate,
                'active' => ['admin/entry/'],
                'icon' => 'book',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ];
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getCategoryGridViewItems(): array
    {
        return !$this->showDefaultCategories ? [] : [
            [
                'label' => Yii::t('cms', 'Categories'),
                'url' => ['/admin/category/index'],
                'visible' => Yii::$app->getUser()->can('categoryUpdate'),
                'active' => ['admin/category/'],
                'icon' => 'folder-open',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getEntryItems(): array
    {
        return array_filter(array_merge($this->getEntryFormItems(), $this->getEntryCategoryItems(), $this->getEntrySectionItems()));
    }

    /**
     * @return array
     */
    protected function getEntryFormItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        return [
            [
                'label' => $this->showEntryTypes ? $entry->getTypeName() : Yii::t('cms', 'Entry'),
                'url' => ['/admin/entry/update', 'id' => $entry->id],
                'visible' => Yii::$app->getUser()->can('entryUpdate', ['entry' => $entry]),
                'active' => array_filter([
                    'admin/entry/update',
                    'admin/cms/asset/' => ['entry'],
                    !$this->isSection() ? 'admin/cms/asset/update' : null,
                ]),
                'icon' => 'book',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getEntryCategoryItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        return !$this->showEntryCategories ? [] : [
            [
                'label' => Yii::t('cms', 'Categories'),
                'url' => ['/admin/entry-category/index', 'entry' => $entry->id],
                'visible' => Yii::$app->getUser()->can('entryCategoryUpdate', ['entry' => $entry]),
                'active' => ['admin/entry-category/'],
                'badge' => $entry->getCategoryCount() ?: false,
                'badgeOptions' => [
                    'id' => 'entry-category-count',
                    'class' => 'badge d-none d-md-inline-block',
                ],
                'icon' => 'folder-open',
                'options' => [
                    'class' => 'entry-sections',
                ],
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getEntrySectionItems(): array
    {
        $entry = $this->isSection() ? $this->model->entry : $this->model;

        return !$this->showEntrySections ? [] : [
            [
                'label' => Yii::t('cms', 'Sections'),
                'url' => ['/admin/section/index', 'entry' => $entry->id],
                'visible' => Yii::$app->getUser()->can('sectionUpdate', ['entry' => $entry]),
                'active' => array_filter([
                    'admin/section/',
                    'admin/cms/asset/' => ['section'],
                    $this->isSection() ? 'admin/cms/asset/update' : null,
                ]),
                'badge' => $entry->section_count ?: false,
                'badgeOptions' => [
                    'id' => 'entry-section-count',
                    'class' => 'badge d-none d-md-inline-block',
                ],
                'icon' => 'th-list',
                'options' => [
                    'class' => 'entry-sections',
                ],
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ],
        ];
    }

    /**
     * Sets breadcrumbs.
     */
    protected function setBreadcrumbs()
    {
        $this->setModuleBreadcrumbs();

        if ($this->model instanceof Category) {
            $this->setCategoryBreadcrumbs();
        } elseif ($this->model) {
            $this->setEntryBreadcrumbs();
        }

        if ($this->isAsset()) {
            $this->setAssetBreadcrumbs();
        }
    }

    /**
     * Sets module breadcrumbs.
     */
    protected function setModuleBreadcrumbs()
    {
        $this->getView()->setBreadcrumb($this->getParentModule()->name, array_merge($this->params, [
            '/admin/entry/index',
            'type' => static::getModule()->defaultEntryType,
            'id' => null,
        ]));
    }

    /**
     * Sets entry breadcrumbs.
     */
    protected function setEntryBreadcrumbs()
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;
        $view = $this->getView();

        if ($this->showEntryTypes) {
            if (isset(Entry::getTypes()[$model->type])) {
                $view->setBreadcrumb(Entry::getTypes()[$model->type]['plural'] ?? Entry::getTypes()[$model->type]['name'], array_merge($this->params, [
                    '/admin/entry/index',
                    'type' => $model->type,
                    'id' => null,
                ]));
            }
        }

        $this->setEntryBreadcrumb();

        if ($this->isSection()) {
            $view->setBreadcrumb(Yii::t('cms', 'Section'), ['/admin/section/update', 'id' => $this->model->id]);
        }
    }

    /**
     * Sets entry breadcrumb.
     */
    protected function setEntryBreadcrumb()
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;
        $this->getView()->setBreadcrumb($this->showEntryTypes ? $model->getTypeName() : Yii::t('cms', 'Entry'), ['/admin/entry/update', 'id' => $model->id]);
    }

    /**
     * Sets category breadcrumbs.
     */
    protected function setCategoryBreadcrumbs()
    {
        $view = $this->getView();
        $view->setBreadcrumb(Yii::t('cms', 'Categories'), ['/admin/category/index']);

        if ($this->parentCategoryBreadcrumbCount > 0) {
            $categories = $this->model->ancestors;
            $count = count($categories);

            if ($count > $this->parentCategoryBreadcrumbCount) {
                $view->setBreadcrumb('…');
            }

            foreach ($categories as $category) {
                if (--$count < $this->parentCategoryBreadcrumbCount) {
                    $view->setBreadcrumb($category->getI18nAttribute('name'), ['/admin/category/update', 'id' => $category->id]);
                }
            }

            $view->setBreadcrumb($this->model->getI18nAttribute('name'), ['/admin/category/update', 'id' => $this->model->id]);
        }
    }

    /**
     * Sets asset breadcrumbs.
     */
    protected function setAssetBreadcrumbs()
    {
        $route = $this->isSection() ? '/admin/section/update' : '/admin/entry/update';
        $this->getView()->setBreadcrumb(Yii::t('cms', 'Assets'), [$route, 'id' => $this->model->id, '#' => 'assets']);
    }

    /**
     * @return Module
     */
    protected function getParentModule(): Module
    {
        $this->_parentModule ??= Yii::$app->getModule('admin')->getModule('cms');
        return $this->_parentModule;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        if ($this->model) {
            if ($route = $this->model->getRoute()) {
                $manager = Yii::$app->getUrlManager();
                $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);
                return Html::tag('div', Html::a(Html::encode($url), $url, ['target' => '_blank']), ['class' => 'small']);
            }
        }

        return '';
    }

    /**
     * @return bool
     */
    protected function isDraft(): bool
    {
        return $this->isSection() ? ($this->model->isDraft() || $this->model->entry->isDraft()) : $this->model->isDraft();
    }

    /**
     * @return bool
     */
    protected function isSection(): bool
    {
        return $this->model instanceof Section;
    }

    /**
     * @return bool
     */
    protected function isAsset(): bool
    {
        return $this->_isAsset;
    }
}