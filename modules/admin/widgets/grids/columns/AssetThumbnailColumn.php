<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\grid\Column;

class AssetThumbnailColumn extends Column
{
    public $headerOptions = [
        'style' => 'width:150px',
    ];

    protected function renderDataCellContent($model, $key, $index): string
    {
        if (!$model->file->hasPreview()) {
            return '';
        }

        return Html::tag('div', '', [
            'style' => 'background-image:url(' . ($model->file->getTransformationUrl('admin') ?: $model->file->getUrl()) . ');',
            'class' => 'thumb',
        ]);
    }
}