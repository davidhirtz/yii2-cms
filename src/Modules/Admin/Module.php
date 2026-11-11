<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsNavItem;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\ScheduledEntriesPanel;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ContentHealthPanel;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Hirtz\Tenant\Web\UrlManager;
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

        if ($health instanceof ContentHealthPanel) {
            $this->addDescriptionChecks($health);

            if (Entry::getModule()->enableCategories) {
                $health->addCheck(
                    'category-unused',
                    static fn (): int => (int)Category::find()->whereUnused()->count(),
                    static fn (int $count): string => Yii::t('cms', 'CATEGORY_UNUSED_TEXT', ['count' => $count]),
                    ['/admin/cms/category/index', 'unused' => 1],
                    [Category::AUTH_CATEGORY],
                    'folder-open',
                );
            }
        }

        return $dashboard->addItem(DashboardItem::make()
            ->icon('pen')
            ->label(Yii::t('cms', 'ENTRY_CREATE_BUTTON'))
            ->order(20)
            ->roles([Entry::AUTH_ENTRY])
            ->url(['/admin/cms/entry/create']))
            ->addPanel(ScheduledEntriesPanel::make()->order(20));
    }

    /**
     * One check per language of the request's tenant, each counted through the grid's own provider, so the number is
     * what the link lists.
     */
    protected function addDescriptionChecks(ContentHealthPanel $health): void
    {
        $languages = $this->getDescriptionLanguages();

        foreach ($languages as $language) {
            $health->addCheck(
                "entry-description-$language",
                static fn (): int => Yii::$container->get(EntryActiveDataProvider::class, config: [
                    'language' => $language,
                    'missing' => EntryActiveDataProvider::MISSING_DESCRIPTION,
                ])->getTotalCount(),
                count($languages) > 1
                    ? static fn (int $count): string => Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_LANGUAGE_TEXT', [
                        'count' => $count,
                        'language' => Yii::$app->getI18n()->getLabel($language),
                    ])
                    : static fn (int $count): string => Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_TEXT', ['count' => $count]),
                [
                    '/admin/cms/entry/index',
                    'missing' => EntryActiveDataProvider::MISSING_DESCRIPTION,
                    'language' => $language !== Yii::$app->sourceLanguage ? $language : null,
                ],
                [Entry::AUTH_ENTRY],
                'triangle-exclamation',
                10,
            );
        }
    }

    /**
     * @return list<string>
     */
    protected function getDescriptionLanguages(): array
    {
        $manager = Yii::$app->getUrlManager();
        $tenant = $manager instanceof UrlManager ? $manager->tenant : null;
        $languages = $tenant?->getAvailableLanguages() ?? Yii::$app->getI18n()->getLanguages();

        return array_values(array_unique([
            ...array_intersect([Yii::$app->sourceLanguage], $languages),
            ...$languages,
        ]));
    }
}
