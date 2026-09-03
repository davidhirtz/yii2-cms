<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class EntryDeleteFrom extends Widget
{
    protected Entry $entry;
    protected string $title;
    protected ?string $confirm = null;
    protected ?string $message = null;

    public function entry(Entry $model): static
    {
        $this->entry = $model;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->entry->isIndex()) {
            $this->title ??= Lang::t('cms', 'COMMON_DELETE_HOMEPAGE');
            $this->confirm ??= Lang::t('cms', 'COMMON_DELETE_TITLE', [
                'slug' => $this->entry::getModule()->entryIndexSlug,
            ]);
        }

        $this->title ??= Lang::t('cms', 'ENTRY_DELETE_FROM_DELETE_ENTRY');

        if ($this->entry->entry_count) {
            $this->message ??= Lang::t('cms', 'ENTRY_DELETE_FROM_WARNING_DELETED_CANNOT');
            $this->confirm ??= Lang::t('cms', 'ENTRY_DELETE_TITLE', [
                'n' => $this->entry->entry_count,
            ]);
        }

        if ($this->entry->section_count) {
            $this->message ??= Lang::t('cms', 'ENTRY_DELETE_FROM_WARNING_DELETED_DELETING');
        }

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        return FormContainer::make()
            ->title($this->title)
            ->form(DeleteActiveForm::make()
                ->model($this->entry)
                ->message($this->message)
                ->confirm($this->confirm));
    }
}
