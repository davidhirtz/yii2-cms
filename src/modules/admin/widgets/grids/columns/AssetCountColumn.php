<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionGridView;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\CounterColumn;

/**
 * @property EntryGridView|SectionGridView $grid
 */
class AssetCountColumn extends CounterColumn
{
    use ModuleTrait;

    /**
     * @var bool|null if set to null, the column will only show if at least one model has descendants enabled
     */
    public $visible = null;

    public function init(): void
    {
        if ($this->visible === null) {
            $this->visible = false;

            if ($this->grid instanceof SectionGridView || static::getModule()->enableEntryAssets) {
                foreach ($this->grid->dataProvider->getModels() as $model) {
                    if ($model->hasAssetsEnabled()) {
                        $this->visible = true;
                        break;
                    }
                }
            }
        }

        $this->route ??= fn(Entry|Section $model) => $model->getAdminRoute() + ['#' => 'assets'];

        parent::init();
    }

    /**
     * @param Entry|Section $model
     */
    protected function renderDataCellContent($model, $key, $index): string
    {
        if (!$model->hasAssetsEnabled()) {
            return '';
        }

        return parent::renderDataCellContent($model, $key, $index);
    }
}
