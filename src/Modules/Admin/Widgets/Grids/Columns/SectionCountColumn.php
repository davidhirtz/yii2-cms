<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
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
        $this->property ??= 'section_count';
        $this->url ??= fn (Entry $model) => ['section/index', 'entry' => $model->id];

        parent::__construct();
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
    protected function getBody(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof Entry && $model->hasSectionsEnabled()
            ? parent::getBody($model, $key, $index)
            : '';
    }
}
