<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Helpers\Url;
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

    #[Override]
    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->items($this->getTypeDropdownItems())
            ->model(Entry::instance())
            ->visible($this->showTypeDropdown);
    }

    protected function getTypeDropdownItems(): array
    {
        $items = Entry::instance()::getTypeDefinitions();
        $entryTypes = $this->provider->section->getEntriesTypes();

        if ($entryTypes !== null) {
            $items = array_intersect_key($items, array_flip($entryTypes));
        }

        return $items;
    }

    /**
     * The picker must not navigate away from itself, which is what a link to the entry did: the name drills into
     * the subentries the way the entry count badge does, and leads nowhere when there are none.
     */
    #[Override]
    protected function getRecordUrl(Entry $entry): array|string|null
    {
        return $entry->hasDescendantsEnabled() && $entry->entry_count
            ? Url::current(['category' => null, 'parent' => $entry->id, 'q' => null, 'type' => null])
            : null;
    }

    /**
     * @see SectionEntryController::actionCreate()
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        yield $this->getAdminLinkButton($entry);

        $canUpdate = $this->webuser->can(Entry::AUTH_ENTRY);

        if (!$canUpdate || $entry->sectionEntry) {
            return;
        }

        $allowedTypes = $this->provider->section->getEntriesTypes();

        if ($allowedTypes === null || in_array($entry->type, $allowedTypes, true)) {
            yield Button::make()
                ->primary()
                ->icon('star')
                ->tooltip(Yii::t('cms', 'SECTION_ENTRY_ADD_TO_SECTION'))
                ->post(['section-entry/create', 'section' => $this->provider->section->id, 'entry' => $entry->id]);
        }
    }

    /**
     * The entry's own page, which the name no longer leads to — in a new tab, so the picker survives the detour.
     */
    protected function getAdminLinkButton(Entry $entry): Stringable
    {
        return Button::make()
            ->secondary()
            ->icon('external-link-alt')
            ->tooltip(Yii::t('cms', 'COMMON_OPEN_ADMIN'))
            ->url($entry->getAdminRoute() ?: null)
            ->target('_blank');
    }
}
