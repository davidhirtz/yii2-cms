<?php

namespace davidhirtz\yii2\cms\modules\admin;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Module
 * @package davidhirtz\yii2\cms\modules\admin
 * @property \davidhirtz\yii2\skeleton\modules\admin\Module $module
 */
class Module extends \yii\base\Module
{
    /**
     * @var string the module display name, defaults to "Entries"
     */
    public $name;

    /**
     * @var mixed the navbar item url
     */
    public $url = ['/admin/entry/index'];

    /**
     * @var string
     */
    public $defaultRoute = 'entry';

    /**
     * @var array containing the admin menu items
     */
    public $navbarItems = [];

    /**
     * @var array containing the panel items
     */
    public $panels = [];

    /**
     * @var array
     */
    protected $defaultControllerMap = [
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
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->name) {
            $this->name = Yii::t('cms', 'Entries');
        }

        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            if (!$this->navbarItems) {
                $this->navbarItems = [
                    'cms' => [
                        'label' => $this->name,
                        'icon' => 'book',
                        'url' => $this->url,
                        'active' => ['admin/category', 'admin/entry', 'admin/entry-category', 'admin/section', 'cms/'],
                        'roles' => ['author'],
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
                                'roles' => ['author'],
                            ],
                            [
                                'label' => Yii::t('cms', 'View All Entries'),
                                'url' => ['/admin/entry/index'],
                                'icon' => 'book',
                                'roles' => ['author'],
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