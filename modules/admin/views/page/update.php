<?php
/**
 * Update page form.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\PageController::actionUpdate()
 *
 * @var \davidhirtz\yii2\cms\modules\admin\components\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 * @var \davidhirtz\yii2\cms\models\Category[] $categories
 * @var yii\bootstrap\ActiveForm $form
 */
use app\components\helpers\Html;
use app\components\widgets\bootstrap\Panel;
use app\components\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\cms\models\Page;
use davidhirtz\yii2\cms\modules\admin\components\widgets\forms\PageActiveForm;
use davidhirtz\yii2\cms\modules\admin\components\widgets\grid\FileGrid;
use davidhirtz\yii2\cms\modules\admin\components\widgets\panels\PageHelpPanel;
use davidhirtz\yii2\cms\modules\admin\components\widgets\nav\Submenu;

$this->setPageTitle(Yii::t('cms', 'Edit Page'));

$this->setPageBreadcrumbs($page);
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($page); ?>

<?= Submenu::widget([
	'title'=>Html::a(Html::encode($page->getOldAttribute('name')), ['/cms/admin/page/update', 'id'=>$page->id]),
	'model'=>$page,
]); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>PageActiveForm::widget([
		'model'=>$page,
	]),
]); ?>

<?php
if(Page::$hasSections)
{
	echo PageHelpPanel::widget([
		'page'=>$page,
		'options'=>[
			'class'=>'page-sections',
		],
	]);
}
?>

<?= Panel::widget([
	'title'=>$page->getAttributeLabel('file_count'),
	'content'=>FileGrid::widget([
		'model'=>$page,
	]),
	'options'=>[
		'class'=>'page-files',
	],
]); ?>

<?= Panel::widget([
	'type'=>'danger',
	'title'=>Yii::t('cms', 'Delete Page'),
	'content'=>DeleteActiveForm::widget([
		'model'=>$page,
	]),
]); ?>