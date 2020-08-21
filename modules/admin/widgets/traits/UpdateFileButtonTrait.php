<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\traits;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class UpdateFileButtonTrait
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\AssetHelpPanel
 *
 * @property Asset $model
 */
trait UpdateFileButtonTrait
{
    /**
     * @return string
     */
    protected function getUpdateFileButton()
    {
        if (Yii::$app->getUser()->can('upload')) {
            return Html::a(Html::iconText('image', Yii::t('media', 'Edit File')), ['/admin/file/update', 'id' => $this->model->file_id], [
                'class' => 'btn btn-secondary',
            ]);
        }

        return '';
    }
}