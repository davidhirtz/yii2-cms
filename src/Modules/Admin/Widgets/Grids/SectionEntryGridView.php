<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\SectionEntryDeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Override;
use Yii;

class SectionEntryGridView extends EntryGridView
{
    protected string $layout = '{header}{summary}{items}{pager}';

    #[Override]
    protected function configure(): void
    {
        $this->rowAttributes ??= fn (Entry $entry) => [
            'class' => $entry->sectionEntry ? ['is-selected'] : [],
        ];

        parent::configure();
    }

    #[Override]
    protected function getTypeDropdownItems(): array
    {
        $items = parent::getTypeDropdownItems();
        $entryTypes = $this->provider->section->getEntriesTypes();

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
            'section' => $this->provider->section,
        ]);

        if (!$canUpdate) {
            return [];
        }

        if ($entry->sectionEntry) {
            return [
                SectionEntryDeleteButton::make($entry, $this->provider->section),
            ];
        }

        $allowedTypes = $this->provider->section->getEntriesTypes();

        if ($allowedTypes && !in_array($entry->type, $allowedTypes, true)) {
            return [];
        }

        return [
            Button::make()
                ->primary()
                ->icon('star')
                ->tooltip(Yii::t('cms', 'Add to section'))
                ->post(['section-entry/create', 'section' => $this->provider->section->id, 'entry' => $entry->id]),
        ];
    }
}
