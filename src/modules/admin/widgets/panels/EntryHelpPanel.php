<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use Stringable;
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
    protected function getReplaceIndexButton(): ?Stringable
    {
        if ($this->model->isIndex()) {
            return null;
        }

        $modal = Modal::make()
            ->title(Yii::t('cms', 'Make homepage'))
            ->html(Yii::t('cms', 'Please confirm that you want to make this entry the new homepage. This will deactivate the current homepage entry.'))
            ->footer(Button::make()
                ->danger()
                ->post(['replace-index', 'id' => $this->model->id], true)
                ->text(Yii::t('cms', 'Make homepage')));

        return Button::make()
            ->danger()
            ->text(Yii::t('cms', 'Make homepage'))
            ->icon('home')
            ->modal($modal);
    }

    /**
     * @see EntryController::actionDuplicate()
     */
    protected function getDuplicateButton(): ?Stringable
    {
        // Todo add modal confirmation for duplicating entries with subentries
        //        if ($this->model->entry_count > 1) {
        //            $options['data-confirm'] ??= Yii::t('cms', 'Do you want to duplicate this entry and its {n} subentries?', [
        //                'n' => Yii::$app->getFormatter()->asInteger($this->model->entry_count),
        //            ]);
        //        }

        return parent::getDuplicateButton();
    }
}
