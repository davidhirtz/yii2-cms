<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
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
                ->title(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_DO_YOU_WANT_TO_DUPLICATE_THIS', [
                    'n' => Yii::$app->getFormatter()->asInteger($this->model->entry_count),
                ]))
                ->footer(Button::make()
                    ->primary()
                    ->text(Lang::t('media', 'ENTRY_ACTION_DROPDOWN_DUPLICATE'))
                    ->post(['duplicate', 'id' => $this->model->id], true));

            return Button::make()
                ->primary()
                ->text(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_DUPLICATE'))
                ->icon('copy')
                ->modal($modal);
        }

        return DuplicateButton::make()
            ->label(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_DUPLICATE'))
            ->model($this->model);
    }

    protected function getLinkButton(): ?Stringable
    {
        return FrontendLinkButton::make()
            ->primary()
            ->model($this->model)
            ->icon('external-link-alt')
            ->text(Lang::t('cms', 'COMMON_OPEN_WEBSITE'))
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
            ->title(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_MAKE_HOMEPAGE'))
            ->content(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_PLEASE_CONFIRM_THAT_YOU_WANT_TO'))
            ->footer(Button::make()
                ->danger()
                ->post(['replace-index', 'id' => $this->model->id], true)
                ->text(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_MAKE_HOMEPAGE')));

        return Button::make()
            ->primary()
            ->text(Lang::t('cms', 'ENTRY_ACTION_DROPDOWN_MAKE_HOMEPAGE'))
            ->icon('home')
            ->modal($modal);
    }

    protected function getEntryDeleteButton(): ?Stringable
    {
        return EntryDeleteButton::make()
            ->model($this->model);
    }
}
