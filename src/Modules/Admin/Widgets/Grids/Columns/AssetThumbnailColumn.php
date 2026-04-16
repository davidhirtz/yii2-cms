<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Media\Models\Interfaces\AssetInterface;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Stringable;
use yii\base\Model;

class AssetThumbnailColumn extends LinkColumn
{
    public function __construct(array $config = [])
    {
        $this->headerAttributes = ['class' => 'grid-col-thumbnail'];
        $this->content ??= $this->getThumbnail(...);

        parent::__construct($config);
    }

    protected function getThumbnail(array|Model $model): string|Stringable
    {
        return $model instanceof AssetInterface ? Thumbnail::make()->file($model->file) : '';
    }
}
