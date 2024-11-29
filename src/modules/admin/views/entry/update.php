<?php
declare(strict_types=1);

/**
 * @see EntryController::actionUpdate()
 *
 * @var View $this
 * @var Entry $entry
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryDeletePanel;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryHelpPanel;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Edit Entry'));
?>

<?= Submenu::widget([
    'model' => $entry,
]); ?>

<?= Html::errorSummary($entry); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => EntryActiveForm::widget([
        'model' => $entry,
    ]),
]); ?>

<?php if ($entry->hasAssetsEnabled()) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => Yii::t('cms', 'Assets'),
        'content' => AssetGridView::widget([
            'parent' => $entry,
        ]),
    ]);
} ?>

<?= EntryHelpPanel::widget([
    'id' => 'operations',
    'model' => $entry,
]); ?>

<?php if (Yii::$app->getUser()->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $entry])) {
    echo EntryDeletePanel::widget([
        'entry' => $entry,
    ]);
} ?>
