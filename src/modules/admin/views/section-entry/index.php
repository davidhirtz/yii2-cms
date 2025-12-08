<?php
declare(strict_types=1);

/**
 * @see \Hirtz\Cms\modules\admin\controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\modules\admin\data\EntryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\SectionEntryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\bootstrap\Panel;

$this->title(Yii::t('cms', 'Link entries'));
?>

<?= CmsSubmenu::widget([
    'model' => $provider->section,
]); ?>

<?php $this->setBreadcrumbs([
    Yii::t('cms', 'Entries') => $provider->section->getAdminRoute() + ['#' => 'entries'],
    Yii::t('cms', 'Link entries'),
]); ?>

<?= Panel::widget([
    'content' => SectionEntryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
