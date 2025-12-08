<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\Entry;
use Hirtz\Cms\modules\admin\controllers\AssetController;
use Hirtz\Cms\modules\admin\controllers\CategoryController;
use Hirtz\Cms\modules\admin\controllers\EntryCategoryController;
use Hirtz\Cms\modules\admin\controllers\EntryController;
use Hirtz\Cms\modules\admin\controllers\SectionController;
use Hirtz\Cms\modules\admin\controllers\SectionEntryController;
use Hirtz\Skeleton\helpers\ArrayHelper;
use Hirtz\Skeleton\modules\admin\config\DashboardItemConfig;
use Hirtz\Skeleton\modules\admin\config\DashboardPanelConfig;
use Hirtz\Skeleton\modules\admin\config\MainMenuItemConfig;
use Hirtz\Skeleton\modules\admin\ModuleInterface;
use Override;
use Yii;

/**
 * @property \Hirtz\Skeleton\modules\admin\Module $module
 */
class Module extends \Hirtz\Skeleton\base\Module implements ModuleInterface
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
}
