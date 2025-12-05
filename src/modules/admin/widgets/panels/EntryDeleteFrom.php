<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

/**
 * @property Entry $model
 */
class EntryDeleteFrom extends Widget
{
    use ModelWidgetTrait;

    protected string $title;
    protected ?string $confirm = null;
    protected ?string $message = null;

    protected function configure(): void
    {
        if ($this->model->isIndex()) {
            $this->title ??= Yii::t('cms', 'Delete Homepage');
            $this->confirm ??= Yii::t('cms', 'Are you sure you want to permanently delete the homepage?', [
                'slug' => $this->model::getModule()->entryIndexSlug,
            ]);
        }

        $this->title ??= Yii::t('cms', 'Delete Entry');

        if ($this->model->entry_count) {
            $this->message ??= Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related subentries and assets will also be unrecoverably deleted. Please be certain!');
            $this->confirm ??= Yii::t('cms', 'Are you sure you want to permanently delete this entry and its related {n,plural,=1{subentry} other{# subentries}}?', [
                'n' => $this->model->entry_count,
            ]);
        }

        if ($this->model->section_count) {
            $this->message ??= Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related sections will also be unrecoverably deleted. Please be certain!');
        }

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        return FormContainer::make()
            ->title($this->title)
            ->form(DeleteActiveForm::make()
                ->model($this->model)
                ->message($this->message)
                ->confirm($this->confirm));
    }
}
