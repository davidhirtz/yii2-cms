<?php

declare(strict_types=1);

/**
 * The default category page. Projects are expected to override this view; it exists so that enabling
 * `Module::$enableCategoryUrls` produces a working page rather than a blank one.
 *
 * @see SiteController::renderCategory()
 *
 * @var View $this
 * @var Category $category
 * @var Entry[] $entries
 */

use Hirtz\Cms\Controllers\SiteController;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Widgets\AdminLink;
use Hirtz\Cms\Widgets\MetaTags;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Web\View;

echo MetaTags::make()
    ->model($category);
?>
<section class="category">
    <h1><?= Html::encode($category->getI18nAttribute('name')); ?></h1>
    <?= $category->getVisibleAttribute('content'); ?>
    <?= AdminLink::tag($category); ?>

    <?php if ($entries) { ?>
        <ul class="entries">
            <?php foreach ($entries as $entry) {
                $url = $entry->getUrl();
                $name = Html::encode((string)$entry->getI18nAttribute('name'));
                ?>
                <li><?= $url ? Html::a($name, $url) : $name; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>
</section>
