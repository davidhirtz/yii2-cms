<?php

namespace davidhirtz\yii2\cms\models\actions;

use app\models\Entry;
use davidhirtz\yii2\cms\models\actions\traits\DuplicateAssetsTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\EntryCategory;
use Yii;

/**
 * @template-implements \davidhirtz\yii2\skeleton\models\actions\DuplicateActiveRecord<Entry>
 */
class DuplicateEntry extends DuplicateActiveRecord
{
    use DuplicateAssetsTrait;

    public function __construct(
        Entry $entry,
        protected ?Entry $parent = null,
        protected bool $shouldUpdateParentAfterInsert = true,
        array $attributes = []
    ) {
        parent::__construct($entry, $attributes);
    }

    protected function beforeDuplicate(): bool
    {
        if (!parent::beforeDuplicate()) {
            return false;
        }

        $this->duplicate->populateParentRelation(!$this->parent || $this->parent->getIsNewRecord()
            ? $this->model->parent
            : $this->parent);

        $this->duplicate->shouldUpdateParentAfterSave = $this->shouldUpdateParentAfterInsert;

        $this->duplicate->asset_count = $this->model->asset_count;
        $this->duplicate->category_ids = $this->model->category_ids;
        $this->duplicate->entry_count = $this->model->entry_count;
        $this->duplicate->section_count = $this->model->section_count;

        $this->duplicate->generateUniqueSlug();

        return true;
    }

    protected function afterDuplicate(): void
    {
        parent::afterDuplicate();

        if ($this->model->asset_count) {
            $this->duplicateAssets();
        }

        if ($this->model->category_ids) {
            $this->duplicateCategories();
        }

        if ($this->model->entry_count) {
            $this->duplicateEntries();
        }

        if ($this->model->section_count) {
            $this->duplicateSections();
        }
    }

    protected function duplicateCategories(): void
    {
        Yii::debug('Duplicating entry categories ...');

        $categories = Category::find()
            ->where(['id' => $this->model->getCategoryIds()])
            ->all();

        foreach ($categories as $category) {
            $entryCategory = EntryCategory::create();
            $entryCategory->populateCategoryRelation($category);
            $entryCategory->populateEntryRelation($this->duplicate);
            $entryCategory->shouldUpdateEntryAfterInsert = false;
            $entryCategory->insert();
        }
    }

    protected function duplicateEntries(): void
    {
        Yii::debug('Duplicating entries ...');

        $entries = $this->model->getChildren(true);
        $position = 0;

        foreach ($entries as $entry) {
            DuplicateEntry::create([
                'entry' => $entry,
                'parent' => $this->duplicate,
                'shouldUpdateParentAfterInsert' => false,
                'attributes' => [
                    'position' => ++$position,
                ],
            ]);
        }
    }

    protected function duplicateSections(): void
    {
        Yii::debug('Duplicating sections ...');

        $sections = $this->model->getSections()->all();
        $position = 0;

        foreach ($sections as $section) {
            DuplicateSection::create([
                'section' => $section,
                'entry' => $this->duplicate,
                'shouldUpdateEntryAfterInsert' => false,
                'attributes' => [
                    'position' => ++$position,
                ],
            ]);
        }
    }

    /**
     * @return Asset[]
     */
    protected function getAssets(): array
    {
        return $this->model->getAssets()
            ->withoutSections()
            ->all();
    }
}