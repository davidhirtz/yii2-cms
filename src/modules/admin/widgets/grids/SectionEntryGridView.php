<?php

declare(strict_types=1);

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
            $this->rowOptions = fn (Entry $entry) => [
                'id' => $this->getRowId($entry),
                'class' => $entry->sectionEntry ? ['is-selected'] : [],
            ];
        }

        parent::init();
    }

    protected function initFooter(): void
    {
    }

    protected function typeDropdownItems(): array
    {
        $items = parent::typeDropdownItems();
        $entryTypes = $this->dataProvider->section->getEntriesTypes();

        if ($entryTypes) {
            $items = array_intersect_key($items, array_flip($entryTypes));
        }

        return $items;
    }

    /**
     * @see SectionEntryController::actionCreate()
     * @see SectionEntryController::actionDelete()
     */
    protected function getRowButtons(Entry $entry): array
    {
        if (Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['section' => $this->dataProvider->section])) {
            $route = [
                'section' => $this->dataProvider->section->id,
                'entry' => $entry->id,
            ];

            $buttons = [
                Html::a((string)Icon::tag('ban'), ['section-entry/delete'] + $route, [
                    'class' => 'btn btn-primary is-selected-block',
                    'title' => Yii::t('cms', 'Remove from section'),
                    'data-toggle' => 'tooltip',
                    'data-ajax' => 'select',
                    'data-target' => '#' . $this->getRowId($entry),
                ])
            ];

            $allowedTypes = $this->dataProvider->section->getEntriesTypes();

            if (!$allowedTypes || in_array($entry->type, $allowedTypes)) {
                $buttons[] = Html::a((string)Icon::tag('star'), ['section-entry/create'] + $route, [
                    'class' => 'btn btn-primary is-selected-hidden',
                    'title' => Yii::t('cms', 'Add to section'),
                    'data-toggle' => 'tooltip',
                    'data-ajax' => 'select',
                    'data-target' => '#' . $this->getRowId($entry),
                ]);
            }

            return $buttons;
        }

        return [];
    }
}
