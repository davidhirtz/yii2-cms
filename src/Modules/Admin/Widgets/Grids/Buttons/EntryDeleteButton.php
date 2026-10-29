<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @see EntryController::actionDelete()
 *
 * @extends DeleteButton<Entry>
 */
class EntryDeleteButton extends DeleteButton
{
    #[\Override]
    public function isVisible(): bool
    {
        return parent::isVisible()
            && $this->webuser->can(Entry::AUTH_ENTRY);
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model->isIndex()) {
            $this->label ??= Yii::t('cms', 'ENTRY_DELETE_INDEX_BUTTON');
            $this->title ??= Yii::t('cms', 'ENTRY_DELETE_INDEX_CONFIRM');
        }

        // The subentries go with it, see `Entry::beforeDelete()`.
        if ($this->model->entry_count) {
            $this->title ??= Yii::t('cms', 'ENTRY_DELETE_BRANCH_CONFIRM', ['n' => $this->model->entry_count]);
            $this->message ??= Yii::t('cms', 'ENTRY_DELETE_WARNING');
        }

        $this->label ??= Yii::t('cms', 'ENTRY_DELETE_BUTTON');
        $this->title ??= Yii::t('cms', 'ENTRY_DELETE_CONFIRM');
        $this->url ??= ['/admin/cms/entry/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
