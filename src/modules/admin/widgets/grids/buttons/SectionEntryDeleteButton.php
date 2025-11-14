<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\buttons;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DeleteButton;
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
