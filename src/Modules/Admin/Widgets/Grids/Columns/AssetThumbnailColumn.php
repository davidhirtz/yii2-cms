<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Media\Models\Interfaces\AssetInterface;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Override;
use Stringable;
use yii\base\Model;

class AssetThumbnailColumn extends LinkColumn
{
    protected string $format = 'raw';
    public ?array $headerAttributes = ['style' => 'width:150px'];

    #[Override]
    protected function getValue(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof AssetInterface
            ? Thumbnail::make()->file($model->file)
            : '';
    }
}
