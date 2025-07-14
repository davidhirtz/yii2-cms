<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\events\FileBeforeDeleteEventHandler;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\web\Application;
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
            'basePath' => '@cms/messages',
        ];

        $app->extendModules([
            'admin' => [
                'modules' => [
                    'cms' => [
                        'class' => modules\admin\Module::class,
                    ],
                ],
            ],
            'cms' => [
                'class' => Module::class,
            ],
            'media' => [
                'class' => \davidhirtz\yii2\media\Module::class,
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

        $app->setMigrationNamespace('davidhirtz\yii2\cms\migrations');
    }

    /**
     * @see Module::$enableUrlRules
     */
    protected function addDefaultUrlRules(): void
    {
        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, function () {
            /** @var Module $module */
            $module = Yii::$app->getModule('cms');

            if ($module->enableUrlRules) {
                Yii::$app->getUrlManager()->addRules($this->getDefaultUrlRules());
            }
        });
    }

    protected function getDefaultUrlRules(): array
    {
        return [
            [
                'route' => 'cms/site/view',
                'pattern' => '<slug:.+>',
                'encodeParams' => false,
            ],
            '' => 'cms/site/index',
        ];
    }
}
