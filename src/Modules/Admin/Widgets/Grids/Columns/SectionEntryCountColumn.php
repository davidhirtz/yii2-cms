<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;

/**
 * @property SectionGridView $grid
 */
class SectionEntryCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->property ??= 'entry_count';
        $this->url ??= fn (Section $section) => $section->getAdminRoute() + ['#' => 'entries'];
    }

    public function isVisible(): bool
    {
        if (!parent::isVisible() || !static::getModule()->enableSectionEntries) {
            return false;
        }

        foreach ($this->grid->provider->getModels() as $section) {
            if ($section->hasEntriesEnabled() && $section->entry_count > 0) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof Section && $model->hasEntriesEnabled()
            ? parent::getBodyContent($model, $key, $index)
            : '';
    }
}
