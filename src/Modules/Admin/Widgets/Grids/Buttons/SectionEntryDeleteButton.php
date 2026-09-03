<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Stringable;
use Yii;

/**
 * @see SectionEntryController::actionDelete()
 */
readonly class SectionEntryDeleteButton implements Stringable
{
    use ContainerConfigurationTrait;

    public function __construct(private Entry $entry, private Section $section)
    {
    }

    public function __toString(): string
    {
        return DeleteGridButton::make()
            ->url(['section-entry/delete', 'section' => $this->section->id, 'entry' => $this->entry->id])
            ->title(Lang::t('cms', 'SECTION_ENTRY_REMOVE_TITLE'))
            ->render();
    }
}
