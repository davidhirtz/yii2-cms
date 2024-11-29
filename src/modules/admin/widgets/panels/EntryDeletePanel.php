<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use Yii;

class EntryDeletePanel extends Panel
{
    public ?Entry $entry = null;

    /**
     * @var string|null the message to display above the "delete" button
     */
    public ?string $message = null;

    /**
     * @var string|null the confirmation message to display when the "delete" button is clicked
     */
    public ?string $confirm = null;

    public string $type = self::TYPE_DANGER;

    public function init(): void
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

        $this->content ??= DeleteActiveForm::widget([
            'model' => $this->entry,
            'message' => $this->message,
            'confirm' => $this->confirm,
        ]);

        parent::init();
    }
}
