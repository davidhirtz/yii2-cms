<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup $group
 * @var int[] $pop
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;

echo '<w>';
echo $group->renderWhere(fn (Section $section): bool => in_array($section->id, $pop, true), '@cmsTestViews/site/_tabs');

foreach ($sections as $section) {
    echo "[$section->id]";
}

echo '</w>';
