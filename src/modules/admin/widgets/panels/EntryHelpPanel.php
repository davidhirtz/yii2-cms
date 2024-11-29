<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * @property Entry $model
 */
class EntryHelpPanel extends HelpPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getDuplicateButton(),
            $this->getReplaceIndexButton(),
            $this->getLinkButton(),
        ]);
    }

    /**
     * @see EntryController::actionReplaceIndex()
     */
    protected function getReplaceIndexButton(array $options = []): string
    {
        if ($this->model->isIndex()) {
            return '';
        }

        return Html::a(Html::iconText('home', Yii::t('cms', 'Make homepage')), ['replace-index', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
            'data-confirm' => Yii::t('cms', 'Please confirm that you want to make this entry the new homepage. This will deactivate the current homepage entry.'),
            ...$options,
        ]);
    }

    /**
     * @see EntryController::actionDuplicate()
     */
    protected function getDuplicateButton(array $options = []): string
    {
        if ($this->model->entry_count > 1) {
            $options['data-confirm'] ??= Yii::t('cms', 'Do you want to duplicate this entry and its {n} subentries?', [
                'n' => Yii::$app->getFormatter()->asInteger($this->model->entry_count),
            ]);
        }

        return parent::getDuplicateButton($options);
    }
}
