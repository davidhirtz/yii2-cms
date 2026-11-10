<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsNavItem;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\ScheduledEntriesPanel;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ContentHealthPanel;
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
        $health = $dashboard->getPanel('health');

        // Counted through the grid's own provider, so the number is what the link lists, tenant included.
        if ($health instanceof ContentHealthPanel) {
            $health->addCheck(
                'entry-description',
                static fn (): int => Yii::$container->get(EntryActiveDataProvider::class, config: [
                    'missing' => EntryActiveDataProvider::MISSING_DESCRIPTION,
                ])->getTotalCount(),
                static fn (int $count): string => Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_TEXT', ['count' => $count]),
                ['/admin/cms/entry/index', 'missing' => EntryActiveDataProvider::MISSING_DESCRIPTION],
                [Entry::AUTH_ENTRY],
            );
        }

        return $dashboard->addItem(DashboardItem::make()
            ->icon('pen')
            ->label(Yii::t('cms', 'ENTRY_CREATE_BUTTON'))
            ->order(20)
            ->roles([Entry::AUTH_ENTRY])
            ->url(['/admin/cms/entry/create']))
            ->addPanel(ScheduledEntriesPanel::make()->order(20));
    }
}
