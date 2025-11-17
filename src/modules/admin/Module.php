<?php

declare(strict_types=1);

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
use davidhirtz\yii2\skeleton\modules\admin\config\DashboardItemConfig;
use davidhirtz\yii2\skeleton\modules\admin\config\DashboardPanelConfig;
use davidhirtz\yii2\skeleton\modules\admin\config\MainMenuItemConfig;
use davidhirtz\yii2\skeleton\modules\admin\ModuleInterface;
use Override;
use Yii;

/**
 * @property \davidhirtz\yii2\skeleton\modules\admin\Module $module
 */
class Module extends \davidhirtz\yii2\skeleton\base\Module implements ModuleInterface
{
    public array|string $url = ['/admin/entry/index'];

    #[Override]
    public function init(): void
    {
        $this->controllerMap = ArrayHelper::merge($this->getCoreControllerMap(), $this->controllerMap);
        parent::init();
    }

    protected function getCoreControllerMap(): array
    {
        $classMap = [
            'asset' => AssetController::class,
            'category' => CategoryController::class,
            'entry' => EntryController::class,
            'entry-category' => EntryCategoryController::class,
            'section' => SectionController::class,
            'section-entry' => SectionEntryController::class,
        ];

        return array_map(fn ($class) => ['class' => $class], $classMap);
    }

    public function getDashboardPanels(): array
    {
        return [
            'cms' => new DashboardPanelConfig(
                name: $this->getName(),
                items: [
                    new DashboardItemConfig(
                        label: Yii::t('cms', 'Create New Entry'),
                        url: ['/admin/entry/create'],
                        icon: 'pen',
                        roles: [Entry::AUTH_ENTRY_CREATE],
                    ),
                    new DashboardItemConfig(
                        label: Yii::t('cms', 'View All Entries'),
                        url: ['/admin/entry/index'],
                        icon: 'book',
                        roles: [Entry::AUTH_ENTRY_UPDATE],
                    ),
                ]
            ),
        ];
    }

    public function getName(): string
    {
        return Yii::t('cms', 'Entries');
    }

    public function getMainMenuItems(): array
    {
        return [
            'cms' => new MainMenuItemConfig(
                label: $this->getName(),
                url: $this->url,
                icon: 'book',
                roles: [Category::AUTH_CATEGORY_UPDATE, Entry::AUTH_ENTRY_UPDATE],
                routes: ['admin/category', 'admin/entry', 'admin/entry-category', 'admin/section', 'cms/'],
            ),
        ];
    }

    public function beforeAction($action): bool
    {
        $this->setViewPath('@cms/modules/admin/views');
        return parent::beforeAction($action);
    }
}
