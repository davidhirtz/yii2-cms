<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionCreate()
 *
 * @var View $this
 * @var Entry $entry
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;

$this->title(Yii::t('cms', 'Create New Entry'));

echo CmsSubmenu::make()
    ->title(Yii::t('cms', 'Entries'))
    ->url(['index']);

echo FormContainer::make()
    ->title($this->title)
    ->form(EntryActiveForm::make()
        ->model($entry));
