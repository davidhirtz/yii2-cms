<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Displays a grid of {@see Entry} models to move or copy the given {@see Section} record to.
 */
class SectionParentEntryGridView extends EntryGridView
{
    /**
     * @var Section|null see {@see SectionController::actionEntries()}
     */
    public ?Section $section = null;

    /**
     * @see SectionController::actionDuplicate()
     * @see SectionController::actionMove()
     */
    protected function getRowButtons(Entry $entry): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($user->can('sectionUpdate', ['entry' => $entry])) {
            $route = [
                'id' => $this->section->id,
                'entry' => $entry->id,
            ];
            $options = [
                'class' => 'btn btn-primary',
                'data-toggle' => 'tooltip',
                'data-method' => 'post',
            ];

            if ($user->can('sectionUpdate', ['section' => $this->section])) {
                $buttons[] = Html::a(Icon::tag('copy'), ['move'] + $route, [
                    ...$options,
                    'title' => Yii::t('cms', 'Move Section'),
                ]);
            }

            $buttons[] = Html::a(Icon::tag('paste'), ['duplicate'] + $route, [
                ...$options,
                'title' => Yii::t('cms', 'Copy Section'),
            ]);
        }

        return $buttons;
    }
}
