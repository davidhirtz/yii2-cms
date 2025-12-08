<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids;

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\controllers\SectionController;
use Hirtz\Skeleton\html\Button;
use Override;
use Yii;

class SectionParentEntryGridView extends EntryGridView
{
    public Section $section;

    /**
     * @see SectionController::actionDuplicate()
     * @see SectionController::actionMove()
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): array
    {
        $user = Yii::$app->getUser();

        if (!$user->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            return [];
        }

        $buttons = [];

        if ($user->can(Section::AUTH_SECTION_UPDATE, ['section' => $this->section])) {
            $buttons[] = Button::make()
                ->primary()
                ->icon('copy')
                ->tooltip(Yii::t('cms', 'Move Section'))
                ->post(['move', 'id' => $this->section->id, 'entry' => $entry->id], true);
        }

        $buttons[] = Button::make()
            ->primary()
            ->icon('paste')
            ->tooltip(Yii::t('cms', 'Copy Section'))
            ->post(['duplicate', 'id' => $this->section->id, 'entry' => $entry->id], true);

        return $buttons;
    }
}
