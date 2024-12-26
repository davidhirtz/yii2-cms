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

        $app->getI18n()->translations['cms'] ??= [
            'class' => PhpMessageSource::class,
            'basePath' => '@cms/messages',
        ];

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
        if (Yii::$app->getModules()['cms']['enableUrlRules'] ?? true) {
            Yii::$app->addUrlManagerRules($this->getDefaultUrlRules());
        }
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
