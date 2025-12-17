<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Controllers\CategoryController;
use Hirtz\Cms\Modules\Admin\Controllers\EntryCategoryController;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardItemConfig;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardPanelConfig;
use Hirtz\Skeleton\Modules\Admin\Config\MainMenuItemConfig;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Override;
use Yii;

/**
 * @property \Hirtz\Skeleton\Modules\Admin\Module $module
 */
class Module extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
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
                'viewPath' => '@cms/../resources/views/admin/asset',
            ],
            'category' => [
                'class' => CategoryController::class,
                'viewPath' => '@cms/../resources/views/admin/category',
            ],
            'entry' => [
                'class' => EntryController::class,
                'viewPath' => '@cms/../resources/views/admin/entry',
            ],
            'entry-category' => [
                'class' => EntryCategoryController::class,
                'viewPath' => '@cms/../resources/views/admin/entry-category',
            ],
            'section' => [
                'class' => SectionController::class,
                'viewPath' => '@cms/../resources/views/admin/section',
            ],
            'section-entry' => [
                'class' => SectionEntryController::class,
                'viewPath' => '@cms/../resources/views/admin/section-entry',
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
