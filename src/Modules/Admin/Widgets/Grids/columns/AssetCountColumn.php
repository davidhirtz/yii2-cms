<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionGridView;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Media\Models\interfaces\AssetParentInterface;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;

/**
 * @property EntryGridView|SectionGridView $grid
 */
class AssetCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->url ??= fn (Entry|Section $model) => $model->getAdminRoute() + ['#' => 'assets'];
    }

    public function isVisible(): bool
    {
        if (!parent::isVisible()) {
            return false;
        }

        foreach ($this->grid->provider->getModels() as $model) {
            if ($model->hasAssetsEnabled()) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof AssetParentInterface && $model->hasAssetsEnabled()
            ? parent::getBodyContent($model, $key, $index)
            : '';
    }
}
