<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\UpdateFileButtonTrait;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;

class AssetActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Asset>
     */
    use ModelTrait;
    use UpdateFileButtonTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getUpdateFileButton(),
            $this->getDuplicateButton(),
            $this->getAssetDeleteButton(),
            $this->getFileDeleteButton(),
        );

        parent::configure();
    }

    /**
     * @see AssetController::actionDuplicate()
     */
    protected function getDuplicateButton(): ?Stringable
    {
        return DuplicateButton::make()
            ->model($this->model);
    }

    /**
     * @see AssetController::actionDelete()
     */
    protected function getAssetDeleteButton(): ?Stringable
    {
        $permission = $this->model->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        return DeleteButton::make()
            ->label(Lang::t('cms', 'ASSET_ACTION_DROPDOWN_DELETE'))
            ->message(Lang::t('cms', 'ASSET_ACTION_DROPDOWN_DELETE_MESSAGE'))
            ->url(['delete', 'id' => $this->model->id])
            ->visible($this->webuser->can($permission, ['asset' => $this->model]))
            ->model($this->model);
    }

    /**
     * @see FileController::actionDelete()
     */
    protected function getFileDeleteButton(): ?Stringable
    {
        return DeleteButton::make()
            ->label(Lang::t('media', 'FILE_ACTION_DROPDOWN_DELETE_FILE'))
            ->message(Lang::t('cms', 'ASSET_ACTION_DROPDOWN_DELETE_FILE_MESSAGE'))
            ->url(['/admin/media/file/delete', 'id' => $this->model->file_id])
            ->visible($this->webuser->can(File::AUTH_FILE_DELETE, ['file' => $this->model->file]))
            ->model($this->model->file);
    }
}
