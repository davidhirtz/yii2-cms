<?php
/**
 * Entries.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ActiveDataProvider $provider
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm $entry
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Entries'));
$this->setBreadcrumb($this->title, ['index']);
?>

<?= EntrySubmenu::widget([
    'entry' => $entry,
]); ?>

<?= Panel::widget([
    'content' => EntryGridView::widget([
        'dataProvider' => $provider,
        'entry' => $entry,
    ]),
]); ?>