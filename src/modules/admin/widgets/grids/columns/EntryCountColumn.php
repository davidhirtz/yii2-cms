<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\grids\columns\CounterColumn;
use yii\helpers\Url;

/**
 * @property EntryGridView $grid
 */
class EntryCountColumn extends CounterColumn
{
    use ModuleTrait;

    #[\Override]
    public function init(): void
    {
        if ($this->visible) {
            $this->visible = false;

            if (static::getModule()->enableNestedEntries) {
                foreach ($this->grid->dataProvider->getModels() as $model) {
                    if ($model->hasDescendantsEnabled()) {
                        $this->visible = true;
                        break;
                    }
                }
            }
        }

        $this->route ??= fn (Entry $model) => Url::current(['parent' => $model->id, 'type' => null, 'q' => null]);

        parent::init();
    }

    /**
     * @param Entry $model
     */
    #[\Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        if (!$model->hasDescendantsEnabled()) {
            return '';
        }

        return parent::renderDataCellContent($model, $key, $index);
    }
}
