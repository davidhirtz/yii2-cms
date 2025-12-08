<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\SectionEntryController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Buttons\SectionEntryDeleteButton;
use Hirtz\Skeleton\Widgets\Grids\Buttons\CreateButton;
use Override;
use Stringable;
use Yii;
use yii\helpers\Inflector;

/**
 * @property EntryActiveDataProvider|null $dataProvider
 */
class SectionLinkedEntryGridView extends EntryGridView
{
    public Section $section;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'section-entry-grid');

        $this->rowAttributes ??= function (Entry $entry): array {
            $allowedTypes = $this->dataProvider->section->getEntriesTypes();

            return [
                'id' => implode('-', [
                    Inflector::camel2id($entry->sectionEntry->formName()),
                    ...$entry->sectionEntry->getPrimaryKey(true),
                ]),
                'class' => $allowedTypes && !in_array($entry->type, $allowedTypes, true)
                    ? ['invalid']
                    : null,
            ];
        };

        $this->dataProvider ??= Yii::$container->get(EntryActiveDataProvider::class, [], [
            'section' => $this->section,
            'pagination' => false,
        ]);

        $this->layout = $this->section->entry_count ? '{items}{footer}' : '{footer}';

        /** @see SectionEntryController::actionOrder() */
        $this->orderRoute = ['section-entry/order', 'section' => $this->dataProvider->section->id];

        parent::init();
    }

    #[Override]
    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getSelectEntriesButton(),
            ],
        ];
    }

    protected function getSelectEntriesButton(): ?Stringable
    {
        $entryTypes = $this->dataProvider->section->getEntriesTypes();

        return Yii::createObject(CreateButton::class, [
            Yii::t('cms', 'Link entries'),
            [
                'section-entry/index',
                'section' => $this->dataProvider->section->id,
                'type' => $entryTypes ? current($entryTypes) : null,
            ],
            'link'
        ]);
    }

    #[Override]
    protected function getButtonColumnContent(Entry $entry): array
    {
        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            return [];
        }

        $buttons = [];

        if ($this->isSortable() && $this->dataProvider->getCount() > 1) {
            $buttons[] = $this->getSortableButton();
        }

        $buttons[] = Yii::createObject(SectionEntryDeleteButton::class, [$entry, $this->dataProvider->section]);

        return $buttons;
    }
}
