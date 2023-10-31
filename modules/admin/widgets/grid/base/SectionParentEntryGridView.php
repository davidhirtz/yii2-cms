<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Displays a grid of {@link Entry} models to move or copy the given {@link Section} record to.
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionParentEntryGridView
 */
class SectionParentEntryGridView extends \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView
{
    /**
     * @var Section|null see {@link SectionController::actionEntries()}
     */
    public ?Section $section = null;

    protected function getRowButtons(Entry $entry): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($user->can('sectionUpdate', ['entry' => $entry])) {
            $options = [
                'class' => 'btn btn-primary',
                'data-toggle' => 'tooltip',
                'data-method' => 'post',
                'data-params' => [Html::getInputName($this->section, 'entry_id') => $entry->id],
            ];

            if ($user->can('sectionUpdate', ['section' => $this->section])) {
                $buttons[] = Html::a(Icon::tag('copy'), ['update', 'id' => $this->section->id], array_merge($options, [
                    'title' => Yii::t('cms', 'Move Section'),
                ]));
            }

            $buttons[] = Html::a(Icon::tag('paste'), ['clone', 'id' => $this->section->id], array_merge($options, [
                'title' => Yii::t('cms', 'Copy Section'),
            ]));
        }

        return $buttons;
    }
}