<?php
declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup $group
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\AdminLink;

// `relative` is the positioned ancestor `AdminLink`'s overlay fills — a project overriding this view owns it,
// whether through Tailwind or a rule of its own, or the overlay covers the viewport instead of the section.
foreach ($sections as $section) {
    // A section carrying a block has no content of its own; the block is what it renders.
    $record = $section->getVisibleBlock() ?? $section;
    ?>
    <section class="relative <?= $section->getCssClass(); ?>" id="<?= $section->getHtmlId(); ?>">
        <?php if ($assets = $section->getVisibleAssets()) {
            echo Gallery::make()->assets($assets);
        } ?>
        <?= $record->getVisibleAttribute('content'); ?>
        <?= AdminLink::tag($section); ?>
    </section>
    <?php
} ?>
