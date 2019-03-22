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
     * @var string the module display name, defaults to "Entries"
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
    public $defaultRoute = 'entry';

    /**
     * @var array
     */
    protected $defaultControllerMap = [
        'entry' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\EntryController',
            'viewPath' => '@cms/modules/admin/views/entry',
        ],
        'asset' => [
            'class' => 'davidhirtz\yii2\cms\modules\admin\controllers\AssetController',
            'viewPath' => '@cms/modules/admin/views/asset',
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
                        'label' => $this->name ?: Yii::t('cms', 'Entries'),
                        'icon' => 'book',
                        'url' => ['/admin/entry/index'],
                        'active' => ['admin/entry', 'admin/section', 'cms/'],
                        'labelOptions' => [
                            'class' => 'hidden-xs',
                        ],
                    ]
                ];
            }
            if (!$this->panels) {
                $this->panels = [
                    [
                        'name' => $this->name ?: Yii::t('cms', 'Entries'),
                        'items' => [
                            [
                                'label' => Yii::t('cms', 'Create New Entry'),
                                'url' => ['/admin/entry/create'],
                                'icon' => 'pen',
                            ],
                            [
                                'label' => Yii::t('cms', 'View All Entries'),
                                'url' => ['/admin/entry/index'],
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

        Yii::$app->getView()->setBreadcrumb(Yii::t('cms', 'Entries'), ['/admin/entry/index']);

        parent::init();
    }
}