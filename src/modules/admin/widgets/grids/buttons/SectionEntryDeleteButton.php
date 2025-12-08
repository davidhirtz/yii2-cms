<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids\buttons;

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Skeleton\widgets\grids\buttons\DeleteButton;
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
