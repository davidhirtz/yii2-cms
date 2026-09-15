<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Override;
use Traversable;
use Yii;
use Stringable;

/**
 * @extends EntryGridView<Entry>
 */
class SectionParentEntryGridView extends EntryGridView
{
    protected Section $section;

    public function section(Section $section): static
    {
        $this->section = $section;
        return $this;
    }

    #[Override]
    protected function isPicker(): bool
    {
        return true;
    }

    /**
     * @see SectionController::actionDuplicate()
     * @see SectionController::actionMove()
     * @return Traversable<int, Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        yield $this->getAdminLinkButton($entry);

        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            return;
        }

        yield Button::make()
            ->primary()
            ->icon('copy')
            ->tooltip(Yii::t('cms', 'SECTION_PARENT_ENTRY_MOVE_SECTION'))
            ->post(['move', 'id' => $this->section->id, 'entry' => $entry->id], true);

        yield Button::make()
            ->primary()
            ->icon('paste')
            ->tooltip(Yii::t('cms', 'SECTION_PARENT_ENTRY_COPY_SECTION'))
            ->post(['duplicate', 'id' => $this->section->id, 'entry' => $entry->id], true);
    }
}
