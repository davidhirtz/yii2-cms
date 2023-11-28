<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Displays a grid of {@see Entry} models that can be linked to the given {@see Section} record.
 */
class SectionEntryGridView extends EntryGridView
{
    public function init(): void
    {
        if (!$this->rowOptions) {
            $this->rowOptions = fn(Entry $entry) => [
                'id' => $this->getRowId($entry),
                'class' => $entry->sectionEntry ? ['is-selected'] : [],
            ];
        }

        parent::init();
    }

    protected function initFooter(): void
    {
    }

    /**
     * @see SectionEntryController::actionCreate()
     * @see SectionEntryController::actionDelete()
     */
    protected function getRowButtons(Entry $entry): array
    {
        if (Yii::$app->getUser()->can('sectionUpdate', ['section' => $this->dataProvider->section])) {
            $route = [
                'section' => $this->dataProvider->section->id,
                'entry' => $entry->id,
            ];

            return [
                Html::a(Icon::tag('ban'), ['section-entry/delete'] + $route, [
                    'class' => 'btn btn-primary is-selected-block',
                    'title' => Yii::t('cms', 'Remove from section'),
                    'data-toggle' => 'tooltip',
                    'data-ajax' => 'select',
                    'data-target' => '#' . $this->getRowId($entry),
                ]),
                Html::a(Icon::tag('star'), ['section-entry/create'] + $route, [
                    'class' => 'btn btn-primary is-selected-hidden',
                    'title' => Yii::t('cms', 'Add to section'),
                    'data-toggle' => 'tooltip',
                    'data-ajax' => 'select',
                    'data-target' => '#' . $this->getRowId($entry),
                ]),
            ];
        }

        return [];
    }
}