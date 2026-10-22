<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Tenant\Web\UrlManager;
use Yii;

/**
 * @extends Controller<Module>
 */
abstract class AbstractController extends Controller
{
    use ModuleTrait;

    /**
     * The tenant a grid was rendered for, else the request's, as `EntryActiveDataProvider` resolves it.
     */
    protected function getTenantId(?int $tenant): ?int
    {
        $manager = Yii::$app->getUrlManager();
        return $tenant ?: ($manager instanceof UrlManager ? $manager->tenant?->id : null);
    }
}
