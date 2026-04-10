<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Events\FileBeforeDeleteEventHandler;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
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

        $this->addDefaultUrlRules();

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
            ]
        ];
    }
}
