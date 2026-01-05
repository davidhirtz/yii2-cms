<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Stringable;
use Yii;

/**
 * @template T of Entry
 * @extends AbstractPanel<T>
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
        if ($this->model->entry_count > 1) {
            $modal = Modal::make()
                ->title(Yii::t('cms', 'Do you want to duplicate this entry and its {n} subentries?', [
                    'n' => Yii::$app->getFormatter()->asInteger($this->model->entry_count),
                ]))
                ->footer(Button::make()
                    ->primary()
                    ->text(Yii::t('media', 'Duplicate'))
                    ->post(['duplicate', 'id' => $this->model->id], true));

            return Button::make()
                ->primary()
                ->text(Yii::t('cms', 'Duplicate'))
                ->icon('copy')
                ->modal($modal);
        }

        return parent::getDuplicateButton();
    }
}
