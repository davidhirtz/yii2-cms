<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Collections\MenuCollection;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Events\TenantAfterSaveEventHandler;
use Hirtz\Cms\Models\Events\TenantBeforeDeleteEventHandler;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Skeleton\Base\ConfigBootstrapInterface;
use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Console\Application as ConsoleApplication;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Modules\Admin\Widgets\Grids\TenantGridView;
use Override;
use Yii;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii\i18n\PhpMessageSource;

class Bootstrap implements ConfigBootstrapInterface
{
    #[Override]
    public static function getDefaultConfig(): array
    {
        return [
            'components' => [
                'i18n' => [
                    'translations' => [
                        'cms' => [
                            'class' => PhpMessageSource::class,
                            'basePath' => '@cms/../messages',
                            'forceTranslation' => true,
                        ],
                    ],
                ],
                'search' => [
                    'models' => [
                        Block::class,
                        BlockAsset::class,
                        Category::class,
                        Entry::class,
                        EntryAsset::class,
                        Section::class,
                        SectionAsset::class,
                    ],
                ],
            ],
            'container' => [
                'definitions' => [
                    TenantGridView::class => Modules\Admin\Widgets\Grids\TenantGridView::class,
                ],
            ],
            'modules' => [
                'admin' => [
                    'modules' => [
                        'cms' => [
                            'class' => Modules\Admin\Module::class,
                        ],
                    ],
                ],
                'cms' => [
                    'class' => Module::class,
                    'entryRelations' => [
                        BlockEntry::class,
                        SectionEntry::class,
                    ],
                ],
                'media' => [
                    'class' => \Hirtz\Media\Module::class,
                    'assets' => [
                        BlockAsset::class,
                        EntryAsset::class,
                        SectionAsset::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Application<User>|ConsoleApplication $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@cms', __DIR__);

        // In `bootstrap()`, not the default config: a web application must not route to a console controller
        if ($app instanceof ConsoleApplication) {
            $app->controllerMap['permalink'] ??= Console\Controllers\PermalinkController::class;
        }

        Artwork::reset();
        CategoryCollection::reset();
        MenuCollection::reset();

        $this->addDefaultUrlRules();
        $this->addTenantEventHandlers();
        $this->addPageCacheEventHandlers();

        DashboardController::addRoles(static fn (): array => [
            Entry::AUTH_ENTRY,
            Category::AUTH_CATEGORY,
        ]);

        $app->setMigrationNamespace('Hirtz\Cms\Migrations');

    }

    /**
     * A cached page must not outlive the moment a scheduled entry goes live on it.
     */
    protected function addPageCacheEventHandlers(): void
    {
        EventHelper::on(PageCache::class, PageCache::EVENT_CONFIGURE, function (PageCache $filter): void {
            $module = Yii::$app->getModule('cms');
            $time = $module instanceof Module ? $module->getNextPublishTime() : null;

            if ($time !== null) {
                $seconds = max(1, $time - time());
                $filter->duration = $filter->duration > 0 ? min($filter->duration, $seconds) : $seconds;
            }
        });
    }

    protected function addTenantEventHandlers(): void
    {
        EventHelper::on(
            Tenant::class,
            BaseActiveRecord::EVENT_BEFORE_DELETE,
            fn (Tenant $tenant, ModelEvent $event) => Yii::createObject(TenantBeforeDeleteEventHandler::class, [
                $event,
                $tenant,
            ]),
            ModelEvent::class
        );

        foreach ([BaseActiveRecord::EVENT_AFTER_INSERT, BaseActiveRecord::EVENT_AFTER_UPDATE] as $name) {
            EventHelper::on(
                Tenant::class,
                $name,
                fn () => Yii::createObject(TenantAfterSaveEventHandler::class)
            );
        }
    }

    /**
     * @see Module::$enableUrlRules
     */
    protected function addDefaultUrlRules(): void
    {
        if (Yii::$app->getModules()['cms']['enableUrlRules'] ?? true) {
            Yii::$app->addUrlManagerRules($this->getDefaultUrlRules());
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getDefaultUrlRules(): array
    {
        return [
            [
                'pattern' => '<slug:.+>',
                'route' => 'cms/site/view',
                'encodeParams' => false,
                'position' => 1000,
            ],
            [
                'pattern' => '',
                'route' => 'cms/site/index',
                'position' => 1100,
            ],
        ];
    }
}
