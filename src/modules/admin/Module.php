<?php

namespace davidhirtz\yii2\cms\modules\admin;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\controllers\CategoryController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionController;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionEntryController;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\modules\admin\ModuleInterface;
use Yii;

/**
 * @property \davidhirtz\yii2\skeleton\modules\admin\Module $module
 */
class Module extends \davidhirtz\yii2\skeleton\base\Module implements ModuleInterface
{
    /**
     * @var string|null the module display name, defaults to "Entries"
     */
    public ?string $name = null;

    /**
     * @var array the navbar item url
     */
    public array $route = ['/admin/entry/index'];

    public function init(): void
    {
        $this->controllerMap = ArrayHelper::merge($this->getCoreControllerMap(), $this->controllerMap);

        parent::init();
    }

    protected function getCoreControllerMap(): array
    {
        return [
            'asset' => [
                'class' => AssetController::class,
                'viewPath' => '@cms/modules/admin/views/asset',
            ],
            'category' => [
                'class' => CategoryController::class,
                'viewPath' => '@cms/modules/admin/views/category',
            ],
            'entry' => [
                'class' => EntryController::class,
                'viewPath' => '@cms/modules/admin/views/entry',
            ],
            'entry-category' => [
                'class' => EntryCategoryController::class,
                'viewPath' => '@cms/modules/admin/views/entry-category',
            ],
            'section' => [
                'class' => SectionController::class,
                'viewPath' => '@cms/modules/admin/views/section',
            ],
            'section-entry' => [
                'class' => SectionEntryController::class,
                'viewPath' => '@cms/modules/admin/views/section-entry',
            ],
        ];
    }

    public function getDashboardPanels(): array
    {
        return [
            'cms' => [
                'name' => $this->getName(),
                'items' => [
                    'entry' => [
                        'label' => Yii::t('cms', 'Create New Entry'),
                        'url' => ['/admin/entry/create'],
                        'icon' => 'pen',
                        'roles' => [Entry::AUTH_ENTRY_CREATE],
                    ],
                    'entries' => [
                        'label' => Yii::t('cms', 'View All Entries'),
                        'url' => ['/admin/entry/index'],
                        'icon' => 'book',
                        'roles' => [Entry::AUTH_ENTRY_UPDATE],
                    ],
                ],
            ],
        ];
    }

    public function getName(): string
    {
        return Yii::t('cms', 'Entries');
    }

    public function getNavBarItems(): array
    {
        return [
            'cms' => [
                'label' => $this->getName(),
                'icon' => 'book',
                'url' => $this->route,
                'active' => ['admin/category', 'admin/entry', 'admin/entry-category', 'admin/section', 'cms/'],
                'roles' => [Category::AUTH_CATEGORY_UPDATE, Entry::AUTH_ENTRY_UPDATE],
            ]
        ];
    }
}
