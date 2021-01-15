<?php

namespace davidhirtz\yii2\cms\composer;

use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\cms\bootstrap
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        Yii::setAlias('@cms', dirname(__DIR__));

        $app->extendComponent('i18n', [
            'translations' => [
                'cms' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@cms/messages',
                ],
            ],
        ]);

        $app->extendModules([
            'admin' => [
                'modules' => [
                    'cms' => [
                        'class' => 'davidhirtz\yii2\cms\modules\admin\Module',
                    ],
                ],
            ],
            'cms' => [
                'class' => 'davidhirtz\yii2\cms\Module',
            ],
            'media' => [
                'class' => 'davidhirtz\yii2\media\Module',
                'assets' => [
                    'davidhirtz\yii2\cms\models\Asset',
                ],
            ],
        ]);

        $app->setMigrationNamespace('davidhirtz\yii2\cms\migrations');
    }
}