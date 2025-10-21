<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionEntryController;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\skeleton\helpers\Html;
use Override;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @property EntryActiveDataProvider|null $dataProvider
 */
class SectionLinkedEntryGridView extends EntryGridView
{
    public Section $section;

    #[Override]
    public function init(): void
    {
        if (!$this->rowOptions) {
            $this->rowOptions = $this->getRowOptions(...);
        }

        $this->dataProvider ??= Yii::$container->get(EntryActiveDataProvider::class, [], [
            'section' => $this->section,
            'pagination' => false,
        ]);

        $this->layout = $this->section->entry_count ? '{items}{footer}' : '{footer}';

        /**
         * @see SectionEntryController::actionOrder()
         */
        $this->orderRoute = ['section-entry/order', 'section' => $this->dataProvider->section->id];

        parent::init();
    }

    #[Override]
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

    protected function getRowOptions(Entry $entry): array
    {
        $options = ['id' => $this->getRowId($entry->sectionEntry)];
        $allowedTypes = $this->dataProvider->section->getEntriesTypes();

        if ($allowedTypes && !in_array($entry->type, $allowedTypes)) {
            Html::addCssClass($options, 'invalid');
        }

        return $options;
    }

    protected function getSelectEntriesButton(): string
    {
        $entryTypes = $this->dataProvider->section->getEntriesTypes();

        $route = [
            'section-entry/index',
            'section' => $this->dataProvider->section->id,
            'type' => $entryTypes ? current($entryTypes) : null,
        ];

        return Html::a(Html::iconText('link', Yii::t('cms', 'Link entries')), $route, [
            'class' => 'btn btn-primary',
        ]);
    }

    #[Override]
    protected function getRowButtons(Entry $entry): array
    {
        $buttons = [];

        if (Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            if ($this->dataProvider->getCount() > 1 && $this->isSortedByPosition()) {
                $buttons[] = $this->getSortableButton();
            }

            $buttons[] = $this->getDeleteButton($entry);
        }

        return $buttons;
    }

    /**
     * @param Entry $model
     */
    #[Override]
    protected function getDeleteButton(ActiveRecordInterface $model, array $options = []): string
    {
        return parent::getDeleteButton($model, [
            'icon' => 'ban',
            'class' => 'btn btn-primary',
            'title' => Yii::t('cms', 'Remove from section'),
            'data-toggle' => 'tooltip',
            'data-target' => '#' . $this->getRowId($model->sectionEntry),
            ...$options,
        ]);
    }

    /**
     * @param Entry $model
     */
    #[Override]
    protected function getDeleteRoute(ActiveRecordInterface $model, array $params = []): array
    {
        return ['section-entry/delete', 'section' => $this->dataProvider->section->id, 'entry' => $model->id];
    }
}
