<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Widgets\NavItems;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Events\TenantAfterSaveEventHandler;
use Hirtz\Cms\Models\Events\TenantBeforeDeleteEventHandler;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Modules\Admin\Widgets\Grids\TenantGridView;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@cms', __DIR__);
        Artwork::reset();
        CategoryCollection::reset();
        NavItems::reset();

        $app->getI18n()->translations['cms'] ??= [
            'class' => PhpMessageSource::class,
            'basePath' => '@cms/../messages',
            'forceTranslation' => true,
        ];

        $app->extendComponent('search', [
            'models' => [
                Category::class,
                Entry::class,
                EntryAsset::class,
                Section::class,
                SectionAsset::class,
            ],
        ]);

        $app->extendModules([
            'admin' => [
                'modules' => [
                    'cms' => [
                        'class' => Modules\Admin\Module::class,
                    ],
                ],
            ],
            'cms' => [
                'class' => Module::class,
            ],
            'media' => [
                'class' => \Hirtz\Media\Module::class,
                'assets' => [
                    EntryAsset::class,
                    SectionAsset::class,
                ],
            ],
        ]);

        $this->addDefaultUrlRules();
        $this->addTenantEventHandlers();

        if (!Yii::$container->has(TenantGridView::class)) {
            Yii::$container->set(TenantGridView::class, Modules\Admin\Widgets\Grids\TenantGridView::class);
        }

        DashboardController::addRoles(static fn (): array => [
            Entry::AUTH_ENTRY,
            Category::AUTH_CATEGORY,
        ]);

        $app->setMigrationNamespace('Hirtz\Cms\Migrations');

        $app->controllerMap['permalink'] ??= Console\Controllers\PermalinkController::class;
    }

    protected function addTenantEventHandlers(): void
    {
        EventHelper::on(
            Tenant::class,
            BaseActiveRecord::EVENT_BEFORE_DELETE,
            fn (Tenant $tenant, ModelEvent $event) => Yii::createObject(TenantBeforeDeleteEventHandler::class, [
                $event,
                $tenant,
            ])
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
