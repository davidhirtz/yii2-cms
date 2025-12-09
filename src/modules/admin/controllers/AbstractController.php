<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Navs\NavBar;
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

        $this->getView()->on(View::EVENT_BEFORE_RENDER, function (): void {
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
