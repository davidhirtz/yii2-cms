<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsNavItem;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
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

    public function dashboard(Dashboard $dashboard): Dashboard
    {
        return $dashboard->addItem(DashboardItem::make()
            ->icon('pen')
            ->label(Yii::t('cms', 'Create New Entry'))
            ->roles([Entry::AUTH_ENTRY_CREATE])
            ->url(['/admin/cms/entry/create']));
    }
}
