<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;

;

/**
 * @property EntryGridView $grid
 */
class EntryEntryCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->property ??= 'entry_count';

        $this->url ??= fn (Entry $model) => Url::current([
            'category' => null,
            'parent' => $model->id,
            'q' => null,
            'type' => null,
        ]);

        parent::__construct();
    }

    public function isVisible(): bool
    {
        if (!parent::isVisible() || !static::getModule()->enableNestedEntries) {
            return false;
        }

        foreach ($this->grid->provider->getModels() as $model) {
            if ($model->hasDescendantsEnabled()) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof Entry && $model->hasDescendantsEnabled()
            ? parent::getBodyContent($model, $key, $index)
            : '';
    }
}
