<?php

namespace davidhirtz\yii2\cms\modules\admin;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property \davidhirtz\yii2\skeleton\modules\admin\Module $module
 */
class Module extends \yii\base\Module
{
    /**
     * @var string|null the module display name, defaults to "Entries"
     */
    public ?string $name = null;

    /**
     * @var array|string the navbar item url
     */
    public array|string $url = ['/admin/entry/index'];

    /**
     * @var string
     */
    public $defaultRoute = 'entry';

    /**
     * @var array containing the admin menu items
     */
    public array $navbarItems = [];

    /**
     * @var array containing the panel items
     */
    public array $panels = [];

    
    protected array $defaultControllerMap = [
        'asset' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\AssetController',
            'viewPath' => '@cms/modules/admin/views/asset',
        ],
        'category' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\CategoryController',
            'viewPath' => '@cms/modules/admin/views/category',
        ],
        'entry' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\EntryController',
            'viewPath' => '@cms/modules/admin/views/entry',
        ],
        'entry-category' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController',
            'viewPath' => '@cms/modules/admin/views/entry-category',
        ],
        'section' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\SectionController',
            'viewPath' => '@cms/modules/admin/views/section',
        ],
        'section-entry' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\SectionEntryController',
            'viewPath' => '@cms/modules/admin/views/section-entry',
        ],
    ];

    public function init(): void
    {
        $this->name ??= Yii::t('cms', 'Entries');

        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            if (!$this->navbarItems) {
                $this->navbarItems = [
                    'cms' => [
                        'label' => $this->name,
                        'icon' => 'book',
                        'url' => $this->url,
                        'active' => ['admin/category', 'admin/entry', 'admin/entry-category', 'admin/section', 'cms/'],
                        'roles' => ['categoryUpdate', 'entryUpdate'],
                    ]
                ];
            }

            if (!$this->panels) {
                $this->panels = [
                    'cms' => [
                        'name' => $this->name ?: Yii::t('cms', 'Entries'),
                        'items' => [
                            [
                                'label' => Yii::t('cms', 'Create New Entry'),
                                'url' => ['/admin/entry/create'],
                                'icon' => 'pen',
                                'roles' => ['entryCreate'],
                            ],
                            [
                                'label' => Yii::t('cms', 'View All Entries'),
                                'url' => ['/admin/entry/index'],
                                'icon' => 'book',
                                'roles' => ['entryUpdate'],
                            ],
                        ],
                    ],
                ];
            }

            $this->module->navbarItems = array_merge($this->module->navbarItems, $this->navbarItems);
            $this->module->panels = array_merge($this->module->panels, $this->panels);
        }

        $this->module->controllerMap = ArrayHelper::merge(array_merge($this->module->controllerMap, $this->defaultControllerMap), $this->controllerMap);

        parent::init();
    }
}
