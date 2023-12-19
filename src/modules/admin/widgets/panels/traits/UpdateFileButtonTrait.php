<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

trait UpdateFileButtonTrait
{
    protected function getUpdateFileButton(): string
    {
        if (Yii::$app->getUser()->can('fileCreate')) {
            return Html::a(Html::iconText('image', Yii::t('media', 'Edit File')), ['/admin/file/update', 'id' => $this->model->file_id], [
                'class' => 'btn btn-secondary',
            ]);
        }

        return '';
    }
}
