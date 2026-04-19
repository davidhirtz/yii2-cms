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

class SectionParentEntryGridView extends EntryGridView
{
    protected Section $section;

    public function section(Section $section): static
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @see SectionController::actionDuplicate()
     * @see SectionController::actionMove()
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        if (!$this->webuser->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            yield;
        }

        if ($this->webuser->can(Section::AUTH_SECTION_UPDATE, ['section' => $this->section])) {
            yield Button::make()
                ->primary()
                ->icon('copy')
                ->tooltip(Yii::t('cms', 'Move Section'))
                ->post(['move', 'id' => $this->section->id, 'entry' => $entry->id], true);
        }

        yield Button::make()
            ->primary()
            ->icon('paste')
            ->tooltip(Yii::t('cms', 'Copy Section'))
            ->post(['duplicate', 'id' => $this->section->id, 'entry' => $entry->id], true);
    }
}
