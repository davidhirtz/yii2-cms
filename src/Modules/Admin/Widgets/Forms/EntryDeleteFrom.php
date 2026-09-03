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
            $this->confirm ??= Lang::t('cms', 'COMMON_ARE_YOU_SURE_YOU_WANT_TO', [
                'slug' => $this->entry::getModule()->entryIndexSlug,
            ]);
        }

        $this->title ??= Lang::t('cms', 'ENTRY_DELETE_FROM_DELETE_ENTRY');

        if ($this->entry->entry_count) {
            $this->message ??= Lang::t('cms', 'ENTRY_DELETE_FROM_FLASH_WARNING_DELETING_THIS_ENTRY_CANNOT_BE_ALT');
            $this->confirm ??= Lang::t('cms', 'ENTRY_DELETE_FROM_CONFIRM_ARE_YOU_SURE_YOU_WANT_TO', [
                'n' => $this->entry->entry_count,
            ]);
        }

        if ($this->entry->section_count) {
            $this->message ??= Lang::t('cms', 'ENTRY_DELETE_FROM_FLASH_WARNING_DELETING_THIS_ENTRY_CANNOT_BE');
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
