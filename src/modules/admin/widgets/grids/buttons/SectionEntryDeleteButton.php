<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Grids\Buttons\DeleteButton;
use Stringable;
use Yii;

readonly class SectionEntryDeleteButton implements Stringable
{
    public function __construct(private Entry $entry, private Section $section)
    {
    }

    public function __toString(): string
    {
        return (string)Yii::createObject(DeleteButton::class, [
            'url' => ['section-entry/create', 'section' => $this->section->id, 'entry' => $this->entry->id],
            'message' => Yii::t('cms', 'Are you sure you want to remove this entry from the section?'),
        ]);
    }
}
