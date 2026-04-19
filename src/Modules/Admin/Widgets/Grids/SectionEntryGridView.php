<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\SectionEntryDeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Override;
use Stringable;
use Traversable;
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

    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->items($this->getTypeDropdownItems())
            ->model(Entry::instance())
            ->visible($this->showTypeDropdown);
    }

    protected function getTypeDropdownItems(): array
    {
        $items = Entry::instance()::getTypes();
        $entryTypes = $this->provider->section->getEntriesTypes();

        if ($entryTypes !== null) {
            $items = array_intersect_key($items, array_flip($entryTypes));
        }

        return $items;
    }

    /**
     * @see SectionEntryController::actionCreate()
     * @see SectionEntryController::actionDelete()
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        $canUpdate = Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, [
            'section' => $this->provider->section,
        ]);

        if (!$canUpdate) {
            yield;
        }

        if ($entry->sectionEntry) {
            yield SectionEntryDeleteButton::make($entry, $this->provider->section);
        }

        $allowedTypes = $this->provider->section->getEntriesTypes();

        if ($allowedTypes === null || in_array($entry->type, $allowedTypes, true)) {
            yield Button::make()
                ->primary()
                ->icon('star')
                ->tooltip(Yii::t('cms', 'Add to section'))
                ->post(['section-entry/create', 'section' => $this->provider->section->id, 'entry' => $entry->id]);
        }
    }
}
