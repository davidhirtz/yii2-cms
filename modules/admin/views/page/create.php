<?php
/**
 * Create page form.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\PageController::actionCreate()
 *
 * @var \davidhirtz\yii2\cms\modules\admin\components\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 */
use app\components\helpers\Html;
use app\components\widgets\bootstrap\Panel;
use davidhirtz\yii2\cms\modules\admin\components\widgets\forms\PageActiveForm;
use davidhirtz\yii2\cms\modules\admin\components\widgets\nav\Submenu;

$this->setPageTitle(Yii::t('cms', 'Create New Page'));

$this->setPageBreadcrumbs($page);
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($page); ?>

<?= Submenu::widget([
	'title'=>Html::a(Yii::t('cms', 'Pages'), ['/cms/admin/page/index']),
]); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>PageActiveForm::widget([
		'model'=>$page,
	]),
]); ?>

<div class="page-files alert alert-info">
	<?= Yii::t('cms', 'You can upload files after you created the page.'); ?>
</div>

