<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Models\Interfaces\AssetInterface;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Stringable;

/**
 * @template T of Asset
 * @extends LinkColumn<T>
 */
class AssetThumbnailColumn extends LinkColumn
{
    public function __construct(array $config = [])
    {
        $this->headerAttributes = ['class' => 'grid-col-thumbnail'];
        $this->content ??= $this->getThumbnail(...);

        parent::__construct($config);
    }

    /**
     * @param T $model
     */
    protected function getThumbnail(AssetInterface $model): string|Stringable
    {
        return Thumbnail::make()->file($model->file);
    }
}
