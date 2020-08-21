<?php
/**
 * Entries.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use \davidhirtz\yii2\cms\modules\admin\widgets\nav\EntryToolbar;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Entries'));
?>

<?= Submenu::widget(); ?>
<?= EntryToolbar::widget(); ?>

<?= Panel::widget([
    'content' => EntryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>