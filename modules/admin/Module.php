<?php

namespace davidhirtz\yii2\cms\modules\admin;

use Yii;

/**
 * Class Module
 * @package davidhirtz\yii2\cms\modules\admin
 * @property \davidhirtz\yii2\skeleton\modules\admin\Module $module
 */
class Module extends \yii\base\Module
{
    /**
     * @var string the module display name, defaults to "Pages"
     */
    public $name;

    /**
     * @var array containing the admin menu items
     */
    public $navbarItems = [];

    /**
     * @var array containing the panel items
     */
    public $panels = [];

    /**
     * @var string
     */
    public $defaultRoute = 'page';

    /**
     * @var string
     */
    public $layout = '@skeleton/modules/admin/views/layouts/main';

    /**
     * @var array
     */
    protected $defaultControllerMap = [
        'page' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\PageController',
            'viewPath' => '@cms/modules/admin/views/page',
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
        if(Yii::$app->getUser()->can('author')) {
            if (!$this->navbarItems) {
                $this->navbarItems = [
                    [
                        'label' => $this->name ?: Yii::t('cms', 'Pages'),
                        'icon' => 'book',
                        'url' => ['/admin/page/index'],
                        'active' => ['admin/page', 'admin/section'],
                        'labelOptions' => [
                            'class' => 'hidden-xs',
                        ],
                    ]
                ];
            }
            if (!$this->panels) {
                $this->panels = [
                    [
                        'name' => $this->name ?: Yii::t('cms', 'Pages'),
                        'items' => [
                            [
                                'label' => Yii::t('cms', 'Create New Page'),
                                'url' => ['/admin/page/create'],
                                'icon' => 'pen',
                            ],
                            [
                                'label' => Yii::t('cms', 'View All Pages'),
                                'url' => ['/admin/page/index'],
                                'icon' => 'book',
                            ],
                        ],
                    ],
                ];
            }
        }


        $this->module->navbarItems = array_merge($this->module->navbarItems, $this->navbarItems);
        $this->module->controllerMap = array_merge($this->module->controllerMap, $this->defaultControllerMap, $this->controllerMap);
        $this->module->panels = array_merge($this->module->panels, $this->panels);

        parent::init();
    }
}