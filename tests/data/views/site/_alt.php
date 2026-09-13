<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup<Section> $group
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;

echo '<a view="' . basename($group->getViewFile()) . '" key="' . $group->getKey() . '" wrapper="' . $group->getWrapperKey() . '">';

foreach ($sections as $section) {
    echo "[$section->id:$section->position]";
}

echo '</a>';
