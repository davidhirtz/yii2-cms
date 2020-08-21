<?php
/**
 * Create entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Entry $entry
 */

use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntryToolbar;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Create New Entry'));
?>

<?= Submenu::widget([
    'title' => Html::a(Yii::t('cms', 'Entries'), ['index']),
]); ?>

<?= EntryToolbar::widget([
    'model' => $entry,
]); ?>

<?= Html::errorSummary($entry); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $entry->getActiveForm()::widget([
        'model' => $entry,
    ]),
]); ?>
