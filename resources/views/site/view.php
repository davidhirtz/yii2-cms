<?php

declare(strict_types=1);

/**
 * @see SiteController::actionView()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\Controllers\SiteController;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Widgets\MetaTags;
use Hirtz\Cms\Widgets\Sections;
use Hirtz\Skeleton\Web\View;

echo MetaTags::make()
    ->model($entry);

echo Sections::make()
    ->entry($entry);
