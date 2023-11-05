<?php

namespace davidhirtz\yii2\cms\models\actions;

use app\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\skeleton\models\actions\DuplicateActiveRecordAction;
use Yii;

/**
 * @property Section $model
 * @property Section $duplicate
 */
class DuplicateSectionAction extends DuplicateActiveRecordAction
{
    public int $defaultStatus = Section::STATUS_DRAFT;

    public function __construct(Section $section, protected ?Entry $entry = null, array $attributes = [])
    {
        $attributes['status'] ??= $this->defaultStatus;
        parent::__construct($section, $attributes);
    }

    protected function beforeDuplicate(): bool
    {
        $this->duplicate->populateEntryRelation(!$this->entry || $this->entry->getIsNewRecord()
            ? $this->model->entry
            : $this->entry);

        $this->duplicate->generateUniqueSlug();

        return parent::beforeDuplicate();
    }

    protected function afterDuplicate(): void
    {
        parent::afterDuplicate();

        if ($this->model->asset_count) {
            $this->duplicateAssets();
        }

        if ($this->model->entry_count) {
            $this->duplicateSectionEntries();
        }

        $this->duplicate->update();
    }

    protected function duplicateAssets(): void
    {
        Yii::debug('Duplicating section assets ...');

        $assets = $this->model->getAssets()->all();
        $assetCount = 0;

        foreach ($assets as $asset) {
            $asset->clone([
                'section' => $this->duplicate,
                'position' => ++$assetCount,
            ]);
        }

        $this->duplicate->asset_count = $assetCount;
    }

    protected function duplicateSectionEntries(): void
    {
        Yii::debug('Duplicating section entries ...');

        $entries = $this->model->getEntries()->all();
        $entryCount = 0;

        foreach ($entries as $entry) {
            $sectionEntry = SectionEntry::create();
            $sectionEntry->populateEntryRelation($entry);
            $sectionEntry->populateSectionRelation($this->duplicate);
            $sectionEntry->setIsBatch(true);
            $sectionEntry->position = ++$entryCount;
            $sectionEntry->insert();
        }

        $this->duplicate->entry_count = $entryCount;
    }
}