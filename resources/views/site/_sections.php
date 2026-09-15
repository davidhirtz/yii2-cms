<?php
declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 * @var SectionGroup<Section> $group
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\AdminLink;
use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Skeleton\Web\View;

foreach ($sections as $section) {
    ?>
    <section class="<?= $section->getCssClass(); ?>" id="<?= $section->getHtmlId(); ?>">
        <?php if ($assets = $section->getVisibleAssets()) {
            echo Gallery::make()->assets($assets);
        } ?>
        <?= $section->getVisibleAttribute('content'); ?>
        <?= AdminLink::tag($section); ?>
    </section>
    <?php
} ?>
