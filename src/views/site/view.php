<?php

declare(strict_types=1);

/**
 * @see SiteController::actionView()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\controllers\SiteController;
use Hirtz\Cms\models\Entry;
use Hirtz\Cms\widgets\MetaTags;
use Hirtz\Cms\widgets\Sections;
use Hirtz\Skeleton\web\View;

MetaTags::make(['model' => $entry]);
echo Sections::make(['entry' => $entry]);
