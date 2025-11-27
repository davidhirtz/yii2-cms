<?php
declare(strict_types=1);

/**
 * @see CategoryController::actionCreate()
 *
 * @var View $this
 * @var Category $category
 */

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\CategoryController;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\CategoryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->title(Yii::t('cms', 'Create New Category'));
?>

<?= CmsSubmenu::widget([
    'model' => $category,
]); ?>

<?= Html::errorSummary($category); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => CategoryActiveForm::widget([
        'model' => $category,
    ]),
]); ?>
