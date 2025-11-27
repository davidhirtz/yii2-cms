<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionGridView;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\interfaces\AssetParentInterface;
use davidhirtz\yii2\skeleton\widgets\grids\columns\BadgeColumn;
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
