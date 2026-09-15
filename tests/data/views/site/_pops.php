<?php

declare(strict_types=1);

/**
 * The v2 idiom: every section of the group is handed to the pop helper, which renders the run it starts and
 * answers `''` for every further member of a run it has already taken.
 *
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup $group
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;

echo '<p>';

foreach ($sections as $section) {
    echo $group->renderAdjacent($section, '@cmsTestViews/site/_tabs');
}

echo '</p>';
