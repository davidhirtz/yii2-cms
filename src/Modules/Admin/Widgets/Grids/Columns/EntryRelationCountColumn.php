<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;

class EntryRelationCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->property ??= 'entry_count';
        $this->url ??= fn (EntryRelationModelInterface $model) => $model->getAdminRoute() + ['#' => 'entries'];

        parent::__construct();
    }

    #[Override]
    public function isVisible(): bool
    {
        if (!parent::isVisible()) {
            return false;
        }

        foreach ($this->grid->provider->getModels() as $model) {
            if ($model instanceof EntryRelationModelInterface && $model->allowsEntries() && $model->entry_count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed>|Model $model
     */
    #[Override]
    protected function getBody(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $model instanceof EntryRelationModelInterface && $model->allowsEntries()
            ? parent::getBody($model, $key, $index)
            : '';
    }
}
