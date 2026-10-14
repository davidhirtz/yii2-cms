<?php

declare(strict_types=1);

/**
 * @see SiteController::actionIndex()
 *
 * @var View $this
 */

use Hirtz\Cms\Controllers\SiteController;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Web\View;

$this->title(Yii::t('yii', 'Page not found.'));

$link = A::make()
    ->href(Url::to(['/admin/cms/entry/index']))
    ->text(Yii::t('skeleton', 'COMMON_HERE'));
?>
<h1><?= Yii::t('yii', 'Page not found.'); ?></h1>
<p><?= Yii::t('cms', 'SITE_MISSING_INDEX', ['here' => (string)$link]); ?></p>
