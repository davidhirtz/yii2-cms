<?php
declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionEntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

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
