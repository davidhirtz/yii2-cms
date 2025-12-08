<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids\columns;

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\modules\admin\widgets\grids\EntryGridView;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\widgets\grids\columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;

/**
 * @property EntryGridView $grid
 */
class SectionCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->url ??= fn (Entry $model) => ['section/index', 'entry' => $model->id];
    }

    public function isVisible(): bool
    {
        if (!parent::isVisible() || !static::getModule()->enableSections) {
            return false;
        }

        foreach ($this->grid->provider->getModels() as $model) {
            if ($model->hasSectionsEnabled()) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof Entry && $model->hasSectionsEnabled()
            ? parent::getBodyContent($model, $key, $index)
            : '';
    }
}
