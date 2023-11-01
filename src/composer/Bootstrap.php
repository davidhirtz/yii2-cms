<?php

namespace davidhirtz\yii2\cms\composer;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@cms', dirname(__DIR__));

        $app->extendComponent('i18n', [
            'translations' => [
                'cms' => [
                    'class' => PhpMessageSource::class,
                    'basePath' => '@cms/messages',
                ],
            ],
        ]);

        $app->extendModules([
            'admin' => [
                'modules' => [
                    'cms' => [
                        'class' => \davidhirtz\yii2\cms\modules\admin\Module::class,
                    ],
                ],
            ],
            'cms' => [
                'class' => Module::class,
            ],
            'media' => [
                'class' => \davidhirtz\yii2\media\Module::class,
                'assets' => [
                    Asset::class,
                ],
            ],
        ]);

        $app->setMigrationNamespace('davidhirtz\yii2\cms\migrations');
    }
}