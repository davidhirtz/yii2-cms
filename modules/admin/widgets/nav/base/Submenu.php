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
                'label' => Yii::t('app', 'Entries'),
                'url' => ['/admin/entry/index'],
                'active' => ['admin/entry/'],
                'icon' => 'book',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ];
        }

        if ($this->showDefaultCategories) {
            $items[] = [
                'label' => Yii::t('cms', 'Categories'),
                'url' => ['/admin/category/index'],
                'active' => ['admin/category/'],
                'icon' => 'folder-open',
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
    protected function getEntryItems(): array
    {
        $items = [
            [
                'label' => $this->showEntryTypes ? $this->model->getTypeName() : Yii::t('cms', 'Entry'),
                'url' => ['/admin/entry/update', 'id' => $this->model->id],
                'active' => ['admin/entry/', 'admin/cms/asset/' => ['entry']],
                'icon' => 'book',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ],
        ];

        if (static::getModule()->enableCategories) {
            $items[] = [
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
            ];
        }

        if (static::getModule()->enableSections) {
            $items[] = [
                'label' => Yii::t('cms', 'Sections'),
                'url' => ['/admin/section/index', 'entry' => $this->model->id],
                'active' => ['admin/section/', 'admin/cms/asset/' => ['section']],
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
            ];
        }

        return $items;
    }

    /**
     * Sets breadcrumbs.
     */
    protected function setBreadcrumbs()
    {
        $view = $this->getView();
        $params = Yii::$app->getRequest()->get();
        unset($params['id']);

        if ($this->model instanceof Entry) {
            $view->setBreadcrumb($this->getParentModule()->name, array_merge($params, ['index', 'type' => static::getModule()->defaultEntryType]));

            if($this->showEntryTypes && $this->model->type != static::getModule()->defaultEntryType) {
                $view->setBreadcrumb(Entry::getTypes()[$this->model->type]['plural'] ?? Entry::getTypes()[$this->model->type]['name'], array_merge($params, ['index', 'type' => $this->model->type]));
            }
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