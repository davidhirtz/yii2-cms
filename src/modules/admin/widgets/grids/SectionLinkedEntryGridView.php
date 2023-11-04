<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * Displays a grid of {@see Entry} models linked to the given {@see Section} record.
 */
class SectionLinkedEntryGridView extends EntryGridView
{
    public ?Section $section = null;

    public function init(): void
    {
        if (!$this->rowOptions) {
            $this->rowOptions = fn(Entry $entry) => [
                'id' => $this->getRowId($entry->sectionEntry),
            ];
        }

        $this->dataProvider ??= Yii::$container->get(EntryActiveDataProvider::class, [], [
            'section' => $this->section,
            'pagination' => false,
        ]);

        $this->layout = $this->section->entry_count ? '{items}{footer}' : '{footer}';
        $this->orderRoute = ['section-entry/order', 'section' => $this->dataProvider->section->id];

        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getSelectEntriesButton(),
                    'options' => ['class' => 'offset-md-3 col-md-9'],
                ],
            ],
        ];
    }

    protected function getSelectEntriesButton(): string
    {
        $route = ['section-entry/index', 'section' => $this->dataProvider->section->id];

        return Html::a(Html::iconText('link', Yii::t('cms', 'Link entries')), $route, [
            'class' => 'btn btn-primary',
        ]);
    }

    protected function getRowButtons(Entry $entry): array
    {
        $buttons = [];

        if (Yii::$app->getUser()->can('sectionUpdate', ['entry' => $entry])) {
            if ($this->dataProvider->getCount() > 1) {
                $buttons[] = $this->getSortableButton();
            }

            $buttons[] = $this->getDeleteButton($entry);
        }

        return $buttons;
    }

    /**
     * @param Entry $model
     */
    protected function getDeleteButton(ActiveRecordInterface $model): string
    {
        $route = ['section-entry/delete', 'section' => $this->dataProvider->section->id, 'entry' => $model->id];

        return Html::a(Icon::tag('ban'), $route, [
            'class' => 'btn btn-primary',
            'title' => Yii::t('cms', 'Remove from section'),
            'data-toggle' => 'tooltip',
            'data-ajax' => 'remove',
            'data-target' => '#' . $this->getRowId($model->sectionEntry),
        ]);
    }
}