<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Actions\traits\DuplicateAssetsTrait;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
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

    #[\Override]
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

    #[\Override]
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
