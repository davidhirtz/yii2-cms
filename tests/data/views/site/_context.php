<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup<Section> $group
 * @var string $label
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;

echo '<c same="' . ($this->context === $group ? '1' : '0') . '" label="' . $label . '">';

foreach ($sections as $section) {
    echo "[$section->id]";
}

echo '</c>';
