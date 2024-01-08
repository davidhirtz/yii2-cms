<?php
/**
 * @var View $this
 * @var Section[] $sections
 */

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\widgets\AdminLink;
use davidhirtz\yii2\cms\widgets\Gallery;
use davidhirtz\yii2\skeleton\web\View;

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