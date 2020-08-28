<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class Submenu.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\nav\base
 */
class Submenu extends \davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu
{
    use ModuleTrait;

    /**
     * @var Category|Entry|Section
     */
    public $model;

    /**
     * @var bool whether default categories should be shown as nav items
     */
    public $showDefaultCategories = true;

    /**
     * @var bool whether entry types should be listed as items
     */
    public $showEntryTypes = false;

    /**
     * @var bool whether entry categories should be visible
     */
    public $showEntryCategories = true;

    /**
     * @var bool whether entry sections should be visible
     */
    public $showEntrySections = true;

    /**
     * @var bool whether the website url to given model should be displayed
     */
    public $showUrl = true;

    /**
     * @var string
     */
    private $_parentModule;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;

        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        if (!$this->title) {
            $this->title = $model instanceof Entry ? Html::a($model->getI18nAttribute('name'), ['/admin/entry/update', 'id' => $model->id]) : Html::a($this->getParentModule()->name, $this->getParentModule()->url);
        }

        if ($this->title && $this->showUrl) {
            $this->title .= $this->getUrl();
        }

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $model instanceof Entry ? $model->hasCategoriesEnabled() : static::getModule()->enableCategories;
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $model instanceof Entry ? $model->hasSectionsEnabled() : static::getModule()->enableSections;
        }

        $this->items = array_merge($this->items, $model instanceof Entry ? $this->getEntryItems() : $this->getDefaultItems());
        $this->setBreadcrumbs();

        parent::init();
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
        $items = [];

        if ($this->showEntryTypes) {
            foreach (Entry::getTypes() as $type => $attributes) {
                $items[] = [
                    'label' => $attributes['plural'] ?? $attributes['name'],
                    'url' => ['/admin/entry/index', 'type' => $type],
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
        $model = $this->isSection() ? $this->model->entry : $this->model;

        return [
            [
                'label' => $this->showEntryTypes ? $model->getTypeName() : Yii::t('cms', 'Entry'),
                'url' => ['/admin/entry/update', 'id' => $model->id],
                'active' => array_filter([
                    'admin/entry/',
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
        $model = $this->isSection() ? $this->model->entry : $this->model;

        return !$this->showEntryCategories ? [] : [
            [
                'label' => Yii::t('cms', 'Categories'),
                'url' => ['/admin/entry-category/index', 'entry' => $model->id],
                'active' => ['admin/entry-category/'],
                'badge' => $model->getCategoryCount() ?: false,
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
        $model = $this->isSection() ? $this->model->entry : $this->model;

        return !$this->showEntrySections ? [] : [
            [
                'label' => Yii::t('cms', 'Sections'),
                'url' => ['/admin/section/index', 'entry' => $model->id],
                'active' => array_filter([
                    'admin/section/',
                    'admin/cms/asset/' => ['section'],
                    $this->isSection() ? 'admin/cms/asset/update' : null,
                ]),
                'badge' => $model->section_count ?: false,
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
        $params = Yii::$app->getRequest()->get();
        unset($params['id']);

        $view = $this->getView();
        $view->setBreadcrumb($this->getParentModule()->name, array_merge($params, ['/admin/entry/index', 'type' => static::getModule()->defaultEntryType]));
        $model = $this->isSection() ? $this->model->entry : $this->model;

        if ($model instanceof Entry) {
            if ($this->showEntryTypes) {
                $view->setBreadcrumb(Entry::getTypes()[$model->type]['plural'] ?? Entry::getTypes()[$model->type]['name'], array_merge($params, ['/admin/entry/index', 'type' => $model->type]));
            }

            $this->setEntryBreadcrumbs();
        }
    }

    /**
     * Sets entry breadcrumbs.
     */
    protected function setEntryBreadcrumbs()
    {
        $model = $this->isSection() ? $this->model->entry : $this->model;
        $view = $this->getView();

        $view->setBreadcrumb($this->showEntryTypes ? $model->getTypeName() : Yii::t('cms', 'Entry'), ['/admin/entry/update', 'id' => $model->id]);

        if ($this->isSection()) {
            $view->setBreadcrumb(Yii::t('cms', 'Section'), ['/admin/section/update', 'id' => $this->model->id]);
        }
    }

    /**
     * @return Module
     */
    protected function getParentModule(): Module
    {
        if ($this->_parentModule === null) {
            $this->_parentModule = Yii::$app->getModule('admin')->getModule('cms');
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        return $this->model->isDraft();
    }

    /**
     * @return bool
     */
    protected function isSection(): bool
    {
        return $this->model instanceof Section;
    }
}