<?php
declare(strict_types=1);

/**
 * @see SectionController::actionCreate()
 *
 * @var View $this
 * @var Section $section
 */

use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\controllers\SectionController;
use Hirtz\Cms\modules\admin\widgets\forms\SectionActiveForm;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\bootstrap\Panel;

$this->title(Yii::t('cms', 'Create New Section'));
?>

<?= CmsSubmenu::widget([
    'model' => $section->entry,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => SectionActiveForm::widget([
        'model' => $section,
    ]),
]); ?>
