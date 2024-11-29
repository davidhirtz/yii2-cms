<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\actions\traits\DuplicateAssetsTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use Yii;

/**
 * @extends DuplicateActiveRecord<Section>
 */
class DuplicateSection extends DuplicateActiveRecord
{
    use DuplicateAssetsTrait;

    public function __construct(
        Section $section,
        protected ?Entry $entry = null,
        protected bool $shouldUpdateEntryAfterInsert = true,
        array $attributes = []
    ) {
        parent::__construct($section, $attributes);
    }

    protected function beforeDuplicate(): bool
    {
        $this->duplicate->populateEntryRelation(!$this->entry || $this->entry->getIsNewRecord()
            ? $this->model->entry
            : $this->entry);

        $this->duplicate->shouldUpdateEntryAfterSave = $this->shouldUpdateEntryAfterInsert;
        $this->duplicate->asset_count = $this->model->asset_count;
        $this->duplicate->entry_count = $this->model->entry_count;

        if (!parent::beforeDuplicate()) {
            return false;
        }

        $this->duplicate->generateUniqueSlug();

        return true;
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
    }

    protected function duplicateSectionEntries(): void
    {
        Yii::debug('Duplicating section entries ...');

        $entries = $this->model->getEntries()->all();
        $position = 0;

        foreach ($entries as $entry) {
            $sectionEntry = SectionEntry::create();
            $sectionEntry->populateEntryRelation($entry);
            $sectionEntry->populateSectionRelation($this->duplicate);
            $sectionEntry->setIsBatch(true);
            $sectionEntry->position = ++$position;
            $sectionEntry->insert();
        }
    }
}
