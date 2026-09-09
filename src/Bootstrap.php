<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Events\FileBeforeDeleteEventHandler;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Web\Application;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\ModelEvent;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@cms', __DIR__);

        $app->getI18n()->translations['cms'] ??= [
            'class' => PhpMessageSource::class,
            'basePath' => '@cms/../messages',
                    'forceTranslation' => true,
];

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
                'fileRelations' => [Asset::class],
            ],
        ]);

        $this->addDefaultRoutes();

        ModelEvent::on(
            File::class,
            File::EVENT_BEFORE_DELETE,
            fn (ModelEvent $event) => Yii::createObject(FileBeforeDeleteEventHandler::class, [
                $event,
                $event->sender,
            ])
        );

        DashboardController::addRoles([
            Entry::AUTH_ENTRY_UPDATE,
            Category::AUTH_CATEGORY_UPDATE,
        ]);

        $app->setMigrationNamespace('Hirtz\Cms\Migrations');

        $app->controllerMap['permalink'] ??= Console\Controllers\PermalinkController::class;
    }

    /**
     * @see Module::$enableUrlRules
     */
    protected function addDefaultRoutes(): void
    {
        if (Yii::$app->getModules()['cms']['enableUrlRules'] ?? true) {
            Yii::$app->addRoutes(...$this->getDefaultRoutes());
        }
    }

    /**
     * @return list<Route>
     */
    protected function getDefaultRoutes(): array
    {
        return [
            Route::to('{slug}', 'cms/site/view')
                ->where('slug', Route::PATTERN_PATH)
                ->withoutParamEncoding()
                ->position(Route::POSITION_FALLBACK)
                ->name(Entry::ROUTE_VIEW),
            Route::to('', 'cms/site/index')
                ->position(Route::POSITION_FALLBACK + 100)
                ->name(Entry::ROUTE_INDEX),
        ];
    }

}
