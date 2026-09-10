<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Web\Controller;

/**
 * @extends Controller<Module>
 */
abstract class AbstractController extends Controller
{
    use ModuleTrait;
}
