<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\EntryDeleteButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\FrontendLinkButton;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
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
            return ConfirmButton::make()
                ->icon('copy')
                ->label(Yii::t('cms', 'ENTRY_BUTTON_DUPLICATE'))
                ->title(Yii::t('cms', 'ENTRY_ACTION_DROPDOWN_DUPLICATE_TITLE', [
                    'n' => Yii::$app->getFormatter()->asInteger($this->model->entry_count),
                ]))
                ->url(['duplicate', 'id' => $this->model->id]);
        }

        return DuplicateButton::make()
            ->label(Yii::t('cms', 'ENTRY_BUTTON_DUPLICATE'))
            ->model($this->model);
    }

    protected function getLinkButton(): ?Stringable
    {
        return FrontendLinkButton::make()
            ->primary()
            ->model($this->model)
            ->icon('external-link-alt')
            ->text(Yii::t('cms', 'COMMON_OPEN_WEBSITE'))
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

        return ConfirmButton::make()
            ->confirmStyle('danger')
            ->icon('home')
            ->label(Yii::t('cms', 'ENTRY_ACTION_DROPDOWN_MAKE_HOMEPAGE'))
            ->text(Yii::t('cms', 'ENTRY_ACTION_DROPDOWN_MAKE_TITLE'))
            ->url(['replace-index', 'id' => $this->model->id]);
    }

    protected function getEntryDeleteButton(): ?Stringable
    {
        return EntryDeleteButton::make()
            ->model($this->model);
    }
}
