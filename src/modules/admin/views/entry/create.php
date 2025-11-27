<?php
declare(strict_types=1);

/**
 * Create entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Entry $entry
 */

use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->title(Yii::t('cms', 'Create New Entry'));
?>

<?= CmsSubmenu::widget([
    'title' => Html::a(Yii::t('cms', 'Entries'), ['index']),
]); ?>

<?= Html::errorSummary($entry); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => EntryActiveForm::widget([
        'model' => $entry,
    ]),
]); ?>
