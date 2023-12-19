<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\widgets\NavItems;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use yii\grid\Column;

/**
 * @property EntryGridView $grid
 */
class MenuColumn extends Column
{
    public $contentOptions = [
        'class' => 'text-center',
    ];

    public function init(): void
    {

        if ($this->visible) {
            $this->visible = false;

            foreach ($this->grid->dataProvider->getModels() as $model) {
                if ($this->getIsMenuItem($model)) {
                    $this->visible = true;
                    break;
                }
            }
        }

        parent::init();
    }

    /**
     * @param Entry $model
     */
    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($this->getIsMenuItem($model)) {
            return Icon::solid('stream', [
                'title' => $model->getAttributeLabel('show_in_menu'),
                'data-toggle' => 'tooltip',
            ]);
        }

        return '';
    }

    protected function getIsMenuItem(Entry $entry): bool
    {
        return NavItems::getIsMenuItem($entry);
    }
}