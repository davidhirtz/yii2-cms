<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\grids\columns\BadgeColumn;
use Override;
use Stringable;
use yii\base\Model;
use yii\helpers\Url;

/**
 * @property EntryGridView $grid
 */
class EntryCountColumn extends BadgeColumn
{
    use ModuleTrait;

    public function __construct()
    {
        $this->property ??= 'entry_count';
        $this->url ??= fn (Entry $model) => Url::current(['parent' => $model->id, 'type' => null, 'q' => null]);
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
