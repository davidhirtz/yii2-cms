<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @see EntryController::actionDelete()
 *
 * @property Entry $model
 */
class EntryDeleteButton extends DeleteButton
{
    public function isVisible(): bool
    {
        return parent::isVisible()
            && $this->webuser->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model->isIndex()) {
            $this->label ??= Lang::t('cms', 'COMMON_DELETE_HOMEPAGE');
            $this->title ??= Lang::t('cms', 'COMMON_DELETE_TITLE');
        }

        $this->url ??= ['/admin/cms/entry/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
