<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\CounterColumn;

/**
 * @property EntryGridView $grid
 */
class SectionCountColumn extends CounterColumn
{
    use ModuleTrait;

    #[\Override]
    public function init(): void
    {
        if ($this->visible) {
            $this->visible = false;

            if (static::getModule()->enableSections) {
                foreach ($this->grid->dataProvider->getModels() as $model) {
                    if ($model->hasSectionsEnabled()) {
                        $this->visible = true;
                        break;
                    }
                }
            }
        }

        $this->route ??= fn (Entry $model) => ['section/index', 'entry' => $model->id];

        parent::init();
    }

    /**
     * @param Entry $model
     */
    #[\Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        if (!$model->hasSectionsEnabled()) {
            return '';
        }

        return parent::renderDataCellContent($model, $key, $index);
    }
}
