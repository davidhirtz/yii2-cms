<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\panels;

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\modules\admin\controllers\EntryController;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\widgets\Modal;
use Stringable;
use Yii;

/**
 * @property Entry $model
 */
class EntryPanel extends AbstractPanel
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
            ->content(Yii::t('cms', 'Please confirm that you want to make this entry the new homepage. This will deactivate the current homepage entry.'))
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
