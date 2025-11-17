<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\widgets\navs\NavBar;
use Override;
use Yii;
use yii\base\View;

abstract class AbstractController extends Controller
{
    use ModuleTrait;

    protected array|false|null $i18nTablesRoute = null;

    #[Override]
    public function init(): void
    {
        if (static::getModule()->enableI18nTables) {
            $this->i18nTablesRoute ??= $this->getAdminModuleDefaultRoute();
            $this->initNavBarLanguageRoute();
        }

        parent::init();
    }

    public function initNavBarLanguageRoute(): void
    {
        if (!$this->i18nTablesRoute) {
            return;
        }

        $this->getView()->on(View::EVENT_BEFORE_RENDER, function () {
            if (!Yii::$container->has(NavBar::class)) {
                Yii::$container->set(NavBar::class, [
                    'languageRoute' => $this->i18nTablesRoute,
                ]);
            }
        });
    }

    protected function getAdminModuleDefaultRoute(): array
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        return $module->url;
    }
}
