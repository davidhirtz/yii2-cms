<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

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
            $this->title ??= Yii::t('cms', 'Delete Homepage');
            $this->confirm ??= Yii::t('cms', 'Are you sure you want to permanently delete the homepage?', [
                'slug' => $this->entry::getModule()->entryIndexSlug,
            ]);
        }

        $this->title ??= Yii::t('cms', 'Delete Entry');

        if ($this->entry->entry_count) {
            $this->message ??= Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related subentries and assets will also be unrecoverably deleted. Please be certain!');
            $this->confirm ??= Yii::t('cms', 'Are you sure you want to permanently delete this entry and its related {n,plural,=1{subentry} other{# subentries}}?', [
                'n' => $this->entry->entry_count,
            ]);
        }

        if ($this->entry->section_count) {
            $this->message ??= Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related sections will also be unrecoverably deleted. Please be certain!');
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
