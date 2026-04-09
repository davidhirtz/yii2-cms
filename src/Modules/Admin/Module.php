<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsNavItem;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardItem;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Yii;

/**
 * @property \Hirtz\Skeleton\Modules\Admin\Module $module
 */
class Module extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
{
    public $defaultRoute = 'entry';

    public function aside(Nav $nav): Nav
    {
        return $nav->addItem(CmsNavItem::make());
    }

    public function getDashboardPanels(): array
    {
        return [
            'cms' => new DashboardPanel(
                name: Yii::t('cms', 'Entries'),
                items: [
                    new DashboardItem(
                        label: Yii::t('cms', 'Create New Entry'),
                        url: ['/admin/cms/entry/create'],
                        icon: 'pen',
                        roles: [Entry::AUTH_ENTRY_CREATE],
                    ),
                    new DashboardItem(
                        label: Yii::t('cms', 'View All Entries'),
                        url: ['/admin/cms/entry/index'],
                        icon: 'book',
                        roles: [Entry::AUTH_ENTRY_UPDATE],
                    ),
                ]
            ),
        ];
    }
}
