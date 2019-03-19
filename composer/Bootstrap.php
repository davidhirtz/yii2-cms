<?php

namespace davidhirtz\yii2\cms\composer;

use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
use yii\helpers\ArrayHelper;

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

        $components = $app->getComponents();
        $modules = $app->getModules();

        if (!isset($modules['cms']['class'])) {
            $app->setModule('cms', ArrayHelper::merge($modules['admin'], [
                'class' => 'davidhirtz\yii2\cms\Module',
            ]));
        }

        if (!isset($modules['admin']['modules']['cms']['class'])) {
            $app->setModule('admin', ArrayHelper::merge($modules['admin'], [
                'modules' => [
                    'cms' => [
                        'class' => 'davidhirtz\yii2\cms\modules\admin\Module',
                    ],
                ],
            ]));
        }

        if (!isset($components['i18n']['translations']['cms']['class'])) {
            $app->setComponents([
                'i18n' => ArrayHelper::merge($components['i18n'], [
                    'translations' => [
                        'cms' => [
                            'class' => 'yii\i18n\PhpMessageSource',
                            'basePath' => '@cms/messages',
                        ],
                    ],
                ]),
            ]);
        }

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