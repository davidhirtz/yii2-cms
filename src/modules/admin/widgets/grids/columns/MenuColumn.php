<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use app\modules\admin\widgets\EntryGridView;
use davidhirtz\yii2\cms\models\traits\MenuAttributeTrait;
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

    public $visible = null;

    public function init(): void
    {

        if ($this->visible === null) {
            $this->visible = false;

            /** @var MenuAttributeTrait $model */
            foreach ($this->grid->dataProvider->getModels() as $model) {
                if ($model->hasShowInMenuEnabled() && $model->isMenuItem()) {
                    $this->visible = true;
                    break;
                }
            }
        }

        parent::init();
    }

    /**
     * @param MenuAttributeTrait $model
     */
    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($model->isMenuItem()) {
            return Icon::solid('stream', [
                'title' => $model->getAttributeLabel('show_in_menu'),
                'data-toggle' => 'tooltip',
            ]);
        }

        return '';
    }
}