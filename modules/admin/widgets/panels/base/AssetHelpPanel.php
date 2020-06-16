<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class AssetHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\AssetHelpPanel
 *
 * @property Asset $model
 */
class AssetHelpPanel extends HelpPanel
{
    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getUpdateFileButton(),
        ]);
    }

    /**
     * @return string
     */
    protected function getUpdateFileButton()
    {
        return Html::a(Html::iconText('image', Yii::t('media', 'Edit File')), ['/admin/file/update', 'id' => $this->model->file_id], [
            'class' => 'btn btn-primary',
        ]);
    }
}