<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Web\View;

echo '<t>';

foreach ($sections as $section) {
    echo "[$section->id]";
}

echo '</t>';
