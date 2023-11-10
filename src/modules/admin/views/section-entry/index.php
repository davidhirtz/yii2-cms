<?php
/**
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionEntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Link entries'));
?>

<?= Submenu::widget([
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