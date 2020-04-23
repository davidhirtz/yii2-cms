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
     * @var Category|Entry
     */
    public $model;

    /**
     * @var bool
     */
    public $showDefaultCategories = true;

    /**
     * @var bool whether entry types should be listed as items.
     */
    public $showEntryTypes = false;

    /**
     * @var bool whether entry categories should be visible.
     */
    public $showEntryCategories = true;

    /**
     * @var bool whether entry sections should be visible.
     */
    public $showEntrySections = true;

    /**
     * @var bool
     */
    public $isSection = false;

    /**
     * @var string
     */
    private $_parentModule;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        if ($this->model instanceof Section) {
            $this->model = $this->model->entry;
            $this->isSection = true;
        }

        if ($this->showEntryCategories) {
            $this->showEntryCategories = $this->model instanceof Entry ? $this->model->hasCategoriesEnabled() : static::getModule()->enableCategories;
        }

        if ($this->showEntrySections) {
            $this->showEntrySections = $this->model instanceof Entry ? $this->model->hasSectionsEnabled() : static::getModule()->enableSections;
        }

        if (!$this->title) {
            $this->title = $this->model instanceof Entry ? Html::a($this->model->getI18nAttribute('name'), ['/admin/entry/update', 'id' => $this->model->id]) : Html::a($this->getParentModule()->name, $this->getParentModule()->url);
        }

        $this->items = array_merge($this->items, $this->model instanceof Entry ? $this->getEntryItems() : $this->getDefaultItems());
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
        return [
            [
                'label' => $this->showEntryTypes ? $this->model->getTypeName() : Yii::t('cms', 'Entry'),
                'url' => ['/admin/entry/update', 'id' => $this->model->id],
                'active' => array_filter([
                    'admin/entry/',
                    'admin/cms/asset/' => ['entry'],
                    !$this->isSection ? 'admin/cms/asset/update' : null,
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
        return !$this->showEntryCategories ? [] : [
            [
                'label' => Yii::t('cms', 'Categories'),
                'url' => ['/admin/entry-category/index', 'entry' => $this->model->id],
                'active' => ['admin/entry-category/'],
                'badge' => $this->model->getCategoryCount() ?: false,
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
        return !$this->showEntrySections ? [] : [
            [
                'label' => Yii::t('cms', 'Sections'),
                'url' => ['/admin/section/index', 'entry' => $this->model->id],
                'active' => array_filter([
                    'admin/section/',
                    'admin/cms/asset/' => ['section'],
                    $this->isSection ? 'admin/cms/asset/update' : null,
                ]),
                'badge' => $this->model->section_count ?: false,
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
        $view = $this->getView();
        $view->setBreadcrumb($this->getParentModule()->name, ['/admin/entry/index', 'type' => static::getModule()->defaultEntryType]);

        if ($this->model instanceof Entry && $this->showEntryTypes) {
            $params = Yii::$app->getRequest()->get();
            unset($params['id']);

            $view->setBreadcrumb(Entry::getTypes()[$this->model->type]['plural'] ?? Entry::getTypes()[$this->model->type]['name'], array_merge($params, ['/admin/entry/index', 'type' => $this->model->type]));
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

        return $this->_parentModule;
    }
}