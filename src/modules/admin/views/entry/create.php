<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\modules\admin\controllers\EntryController::actionCreate()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\modules\admin\widgets\forms\EntryActiveForm;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\FormContainer;

$this->title(Yii::t('cms', 'Create New Entry'));

echo CmsSubmenu::make()
    ->title(Yii::t('cms', 'Entries'))
    ->url(['index']);

echo FormContainer::make()
    ->title($this->title)
    ->form(EntryActiveForm::make()
        ->model($entry));
