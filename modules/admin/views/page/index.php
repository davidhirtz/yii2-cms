<?php
/**
 * Admin page list.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\PageController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ActiveDataProvider $provider
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\PageGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\PageSubmenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Pages'));
$this->setBreadcrumb($this->title, ['index']);
?>

<?= PageSubmenu::widget([
    'page' => $page,
]); ?>

<?= Panel::widget([
    'content' => PageGridView::widget([
        'dataProvider' => $provider,
        'page' => $page,
    ]),
]); ?>