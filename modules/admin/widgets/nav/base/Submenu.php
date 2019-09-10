<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
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
     * @var CategoryForm|EntryForm
     */
    public $model;

    /**
     * @var bool
     */
    public $showDefaultCategories = true;

    /**
     * @var bool
     */
    public $showEntryTypes = false;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if ($this->showDefaultCategories) {
            $this->showDefaultCategories = static::getModule()->enableCategories;
        }

        if ($this->model instanceof SectionForm) {
            $this->model = $this->model->entry;
        }

        if (!$this->title) {
            /** @var Module $module */
            $module = Yii::$app->getModule('admin')->getModule('cms');
            $this->title = $this->model instanceof EntryForm ? Html::a($this->model->getI18nAttribute('name'), ['/admin/entry/update', 'id' => $this->model->id]) : $module->name;
        }

        $this->items = array_merge($this->items, $this->model instanceof EntryForm ? $this->getEntryItems() : $this->getDefaultItems());

        parent::init();
    }

    /**
     * @return array
     */
    protected function getDefaultItems(): array
    {
        $items = [];

        if ($this->showEntryTypes) {
            foreach (EntryForm::getTypes() as $type => $attributes) {
                $items[] = [
                    'label' => $attributes[isset($attributes['plural']) ? 'plural' : 'name'],
                    'url' => ['/admin/entry/index', 'type' => $type],
                    'icon' => $attributes['icon'] ?? 'book',
                    'labelOptions' => [
                        'class' => 'd-none d-md-inline'
                    ],
                ];
            }
        } else {
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
                'visible' => static::getModule()->enableCategories,
                'badge' => $this->model->getCategoryCount() ?: false,
                'badgeOptions' => [
                    'id' => 'entry-category-count',
                    'class' => 'badge d-none d-md-inline-block',
                ],
                'icon' => 'folder-open',
                'active' => ['admin/entry-category/'],
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
                'badge' => $this->model->section_count ?: false,
                'badgeOptions' => [
                    'id' => 'entry-section-count',
                    'class' => 'badge d-none d-md-inline-block',
                ],
                'icon' => 'th-list',
                'active' => ['admin/section/'],
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
}