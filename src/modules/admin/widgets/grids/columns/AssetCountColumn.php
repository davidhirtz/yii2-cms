<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids\columns;

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\widgets\grids\EntryGridView;
use Hirtz\Cms\modules\admin\widgets\grids\SectionGridView;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Media\models\interfaces\AssetParentInterface;
use Hirtz\Skeleton\widgets\grids\columns\BadgeColumn;
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
