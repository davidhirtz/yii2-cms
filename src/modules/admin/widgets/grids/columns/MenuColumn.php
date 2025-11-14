<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\widgets\NavItems;
use davidhirtz\yii2\skeleton\html\Icon;
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
    #[\Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        return $this->getIsMenuItem($model)
            ? Icon::make()
                ->name('stream')
                ->tooltip($model->getAttributeLabel('show_in_menu'))
                ->render()
            : '';
    }

    protected function getIsMenuItem(Entry $entry): bool
    {
        return NavItems::getIsMenuItem($entry);
    }
}
