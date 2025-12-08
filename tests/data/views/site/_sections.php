<?php
declare(strict_types=1);

/**
 * @var View $this
 * @var Section[] $sections
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\widgets\AdminLink;
use Hirtz\Cms\widgets\Gallery;
use Hirtz\Skeleton\Web\View;

foreach ($sections as $section) {
    ?>
    <section class="<?= $section->getCssClass(); ?>" id="<?= $section->getHtmlId(); ?>">
        <?php if ($assets = $section->getVisibleAssets()) {
            echo Gallery::widget(['assets' => $assets]);
        } ?>
        <?= $section->getVisibleAttribute('content'); ?>
        <?= AdminLink::tag($section); ?>
    </section>
    <?php
} ?>
