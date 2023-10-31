<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * Displays a grid of {@link Entry} models linked to the given {@link Section} record to.
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionParentEntryGridView
 */
class SectionEntryGridView extends \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView
{
    public bool $showSectionEntriesOnly = false;

    public function init(): void
    {
        if (!$this->rowOptions) {
            $this->rowOptions = function (Entry $entry) {
                return [
                    'id' => $this->getRowId($entry),
                    'class' => $this->showSectionEntriesOnly && $entry->sectionEntry ? ['is-selected'] : [],
                ];
            };
        }

        if ($this->showSectionEntriesOnly) {
            $this->dataProvider ??= new ArrayDataProvider([
                'allModels' => $this->dataProvider->section->entries,
                'pagination' => false,
            ]);

            $this->layout = '{items}{footer}';
            $this->orderRoute = ['section-entry/order', 'section' => $this->dataProvider->section->id];
        }

        parent::init();
    }

    protected function initFooter(): void
    {
        if ($this->showSectionEntriesOnly) {
            $route = ['section-entry/index', 'section' => $this->dataProvider->section->id];
            $createButton = Html::a(Html::iconText('link', Yii::t('cms', 'Link entries')), $route, [
                'class' => 'btn btn-primary',
            ]);

            $this->footer ??= [
                [
                    [
                        'content' => $createButton,
                        'options' => ['class' => 'offset-md-3 col-md-9'],
                    ],
                ],
            ];
        }

        parent::initFooter();
    }

    protected function getRowButtons(Entry $entry): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($user->can('sectionUpdate', ['entry' => $entry])) {
            if ($this->showSectionEntriesOnly && $this->dataProvider->getCount() > 1) {
                $buttons[] = $this->getSortableButton();
            }

            $route = ['section' => $this->dataProvider->section->id, 'entry' => $entry->id];

            $buttons[] = Html::a(Icon::tag('ban'), ['section-entry/delete'] + $route, [
                'class' => 'btn btn-primary' . (!$this->showSectionEntriesOnly ? ' is-selected-block' : ''),
                'title' => Yii::t('cms', 'Unlink from section'),
                'data-toggle' => 'tooltip',
                'data-ajax' => $this->showSectionEntriesOnly ? 'remove' : 'select',
                'data-target' => '#' . $this->getRowId($entry),
            ]);

            if (!$this->showSectionEntriesOnly) {
                $buttons[] = Html::a(Icon::tag('star'), ['section-entry/create'] + $route, [
                    'class' => 'btn btn-primary is-selected-hidden',
                    'title' => 'Zu Sektion hinzufügen',
                    'data-toggle' => 'tooltip',
                    'data-ajax' => 'select',
                    'data-target' => '#' . $this->getRowId($entry),
                ]);
            }
        }

        return $buttons;
    }
}