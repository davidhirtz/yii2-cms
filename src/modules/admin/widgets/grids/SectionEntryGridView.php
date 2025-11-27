<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\buttons\SectionEntryDeleteButton;
use davidhirtz\yii2\skeleton\html\Button;
use Override;
use Yii;

class SectionEntryGridView extends EntryGridView
{
    public string $layout = '{header}{summary}{items}{pager}';

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'section-entry-grid');

        $this->rowAttributes ??= fn (Entry $entry) => [
            'class' => $entry->sectionEntry ? ['is-selected'] : [],
        ];

        parent::init();
    }

    #[Override]
    protected function getTypeDropdownItems(): array
    {
        $items = parent::getTypeDropdownItems();
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
    #[Override]
    protected function getButtonColumnContent(Entry $entry): array
    {
        $canUpdate = Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, [
            'section' => $this->dataProvider->section,
        ]);

        if (!$canUpdate) {
            return [];
        }

        if ($entry->sectionEntry) {
            return [Yii::createObject(SectionEntryDeleteButton::class, [$entry, $this->dataProvider->section])];
        }

        $allowedTypes = $this->dataProvider->section->getEntriesTypes();

        if ($allowedTypes && !in_array($entry->type, $allowedTypes, true)) {
            return [];
        }

        return [
            Button::make()
                ->primary()
                ->icon('star')
                ->tooltip(Yii::t('cms', 'Add to section'))
                ->post(['section-entry/create', 'section' => $this->dataProvider->section->id, 'entry' => $entry->id]),
        ];
    }
}
