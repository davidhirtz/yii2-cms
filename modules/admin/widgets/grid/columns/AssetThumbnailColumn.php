<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\columns;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\grid\Column;

/**
 * Class AssetThumbnailColumn
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\columns
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
class AssetThumbnailColumn extends Column
{
    /**
     * @var string[]
     */
    public $headerOptions = [
        'style' => 'width:150px',
    ];

    /**
     * @param Asset $model
     * @param string $key
     * @param $index
     * @return string
     */
    protected function renderDataCellContent($model, $key, $index)
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