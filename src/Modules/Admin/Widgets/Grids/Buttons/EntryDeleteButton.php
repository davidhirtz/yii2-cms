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
        }

        $this->message ??= $this->getUsageMessage();

        $this->label ??= Yii::t('cms', 'ENTRY_DELETE_BUTTON');
        $this->title ??= Yii::t('cms', 'ENTRY_DELETE_CONFIRM');
        $this->url ??= ['/admin/cms/entry/delete', 'id' => $this->model->id];

        parent::configure();
    }

    /**
     * What the delete leaves behind: links render as their text, listings lose the entry.
     */
    protected function getUsageMessage(): ?string
    {
        $entry = $this->model;
        $sentences = [];

        if ($entry->entry_count) {
            $sentences[] = Yii::t('cms', 'ENTRY_DELETE_WARNING');
        }

        if ($entry->reference_count) {
            $sentences[] = Yii::t('cms', 'ENTRY_DELETE_REFERENCES_TEXT', [
                'count' => $entry->reference_count,
                'records' => (int)$entry->getReferences()->count(),
            ]);
        }

        if ($entry->relation_count) {
            $sentences[] = Yii::t('cms', 'ENTRY_DELETE_RELATIONS_TEXT', ['count' => $entry->relation_count]);
        }

        return $sentences ? implode(' ', $sentences) : null;
    }
}
