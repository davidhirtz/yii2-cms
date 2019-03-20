<?php

namespace davidhirtz\yii2\cms\composer;

use davidhirtz\yii2\skeleton\composer\BootstrapTrait;
use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\cms\bootstrap
 */
class Bootstrap implements BootstrapInterface
{
    use BootstrapTrait;

    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        Yii::setAlias('@cms', dirname(__DIR__));

        $this->extendComponent($app, 'i18n', [
            'translations' => [
                'cms' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@cms/messages',
                ],
            ],
        ]);

        $this->extendModules($app, [
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
        ]);

        if ($app instanceof \davidhirtz\yii2\skeleton\console\Application) {
            $app->on(Application::EVENT_BEFORE_ACTION, function (yii\base\ActionEvent $event) {
                if ($event->action->controller instanceof MigrateController) {
                    /** @var \davidhirtz\yii2\skeleton\console\controllers\MigrateController $controller */
                    $controller = $event->action->controller;
                    $controller->migrationNamespaces[] = 'davidhirtz\yii2\cms\migrations';
                }
            });
        }
    }
}