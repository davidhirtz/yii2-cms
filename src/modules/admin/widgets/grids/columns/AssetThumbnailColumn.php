<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\modules\admin\widgets\grids\columns\Thumbnail;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\LinkDataColumn;

class AssetThumbnailColumn extends LinkDataColumn
{
    public $headerOptions = ['style' => 'width:150px'];

    #[\Override]
    public function init(): void
    {
        if ($this->content === null) {
            $this->content = $this->renderThumbnail(...);
        }

        parent::init();
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    protected function renderThumbnail(Asset $model, int $key, int $index): string
    {
        return Thumbnail::widget(['file' => $model->file]);
    }
}
