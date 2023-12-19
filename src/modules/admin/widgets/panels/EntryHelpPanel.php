<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use Yii;

/**
 * @property Entry $model
 */
class EntryHelpPanel extends HelpPanel
{
    /**
     * @see EntryController::actionDuplicate()
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getDuplicateButton(),
            $this->getLinkButton(),
        ]);
    }

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
