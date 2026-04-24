<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\EntryDeleteButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\FrontendLinkButton;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use Yii;

class EntryActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getDuplicateButton(),
            $this->getLinkButton(),
            $this->getReplaceIndexButton(),
            $this->getEntryDeleteButton(),
        );

        parent::configure();
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

        return DuplicateButton::make()
            ->label(Yii::t('cms', 'Duplicate'))
            ->model($this->model);
    }

    protected function getLinkButton(): ?Stringable
    {
        return FrontendLinkButton::make()
            ->primary()
            ->model($this->model)
            ->icon('external-link-alt')
            ->text(Yii::t('cms', 'Open website'))
            ->target('_blank');
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
            ->primary()
            ->text(Yii::t('cms', 'Make homepage'))
            ->icon('home')
            ->modal($modal);
    }

    protected function getEntryDeleteButton(): ?Stringable
    {
        return EntryDeleteButton::make()
            ->model($this->model);
    }
}
