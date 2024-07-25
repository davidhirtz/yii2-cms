<?php

namespace davidhirtz\yii2\cms\models\builders;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\collections\FolderCollection;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\BaseObject;

/**
 * @template T of Entry
 */
class EntrySiteRelationsBuilder extends BaseObject
{
    use ModuleTrait;

    public Entry $entry;

    /**
     * @var Asset[]
     */
    public array $assets = [];

    /**
     * @var T[]
     */
    public array $entries = [];

    /**
     * @var File[]
     */
    public array $files = [];

    /**
     * @var bool whether to autoload the current entry's ancestors, this can be useful for breadcrumbs.
     */
    public bool $autoloadEntryAncestors = true;

    protected array $fileIds = [];
    protected array $relatedEntryIds = [];
    protected array $sectionIdsWithAssets = [];
    protected array $sectionIdsWithEntries = [];

    public function init(): void
    {
        ArrayHelper::index($this->entries, 'id');
        ArrayHelper::index($this->files, 'id');

        $this->entries[$this->entry->id] = $this->entry;

        if ($this->autoloadEntryAncestors) {
            $this->relatedEntryIds = [
                ...$this->relatedEntryIds,
                ...array_map('intval', $this->entry->getAncestorIds()),
            ];
        }

        $this->loadRelations();
    }

    protected function loadRelations(): void
    {
        $this->loadSections();

        $this->loadSectionEntries();
        $this->loadEntries();

        $this->populateParentRelations();
        $this->populateSectionEntryRelations();

        $this->loadAssets();
        $this->loadFiles();

        $this->populateAssetRelations();
    }

    protected function loadSections(): void
    {
        if (!$this->entry->section_count) {
            return;
        }

        Yii::debug('Loading related sections ...');

        $sections = $this->entry->getSections()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus()
            ->indexBy('id')
            ->all();

        foreach ($sections as $section) {
            if ($section->hasAssetsEnabled() && $section->asset_count) {
                $this->sectionIdsWithAssets[] = $section->id;
            }

            if ($section->hasEntriesEnabled() && $section->entry_count) {
                $this->sectionIdsWithEntries[] = $section->id;
            }
        }

        $this->entry->populateSectionRelations($sections);
    }

    protected function loadSectionEntries(): void
    {
        if (!$this->sectionIdsWithEntries) {
            return;
        }

        Yii::debug('Loading section entry relations ...');

        /** @var SectionEntry[] $sectionEntries */
        $sectionEntries = SectionEntry::find()
            ->andWhere(['section_id' => $this->sectionIdsWithEntries])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $sectionEntriesBySectionId = [];

        foreach ($sectionEntries as $sectionEntry) {
            $this->relatedEntryIds[] = $sectionEntry->entry_id;
            $sectionEntriesBySectionId[$sectionEntry->section_id][] = $sectionEntry;
        }

        foreach ($this->entry->sections as $section) {
            $section->populateRelation('sectionEntries', $sectionEntriesBySectionId[$section->id] ?? []);
        }
    }

    protected function loadEntries(): void
    {
        if (!$this->relatedEntryIds) {
            return;
        }

        $entryIds = array_unique($this->relatedEntryIds);
        $entryIds = array_diff($entryIds, array_keys($this->entries));

        if ($entryIds) {
            Yii::debug('Loading related entries ...');

            $this->entries += $this->getEntryQuery()
                ->andWhere(['id' => $entryIds])
                ->all();
        }

        if ($this->autoloadEntryAncestors) {
            $this->entry->setAncestors($this->entries);
        }
    }

    protected function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus()
            ->indexBy('id');
    }

    protected function populateParentRelations(): void
    {
        if (!static::getModule()->enableNestedEntries) {
            return;
        }

        foreach ($this->entries as $entry) {
            if (!$entry->isRelationPopulated('parent')) {
                $entry->populateParentRelation($this->entries[$entry->parent_id] ?? null);
            }
        }
    }

    protected function populateSectionEntryRelations(): void
    {
        if (!$this->sectionIdsWithEntries) {
            return;
        }

        foreach ($this->entry->sections as $section) {
            $entries = [];

            if ($section->entry_count) {
                $allowedTypes = $section->getEntriesTypes();

                foreach ($section->sectionEntries as $sectionEntry) {
                    $entry = $this->entries[$sectionEntry->entry_id] ?? null;

                    if ($entry && (!$allowedTypes || in_array($entry->type, $allowedTypes))) {
                        $entries[$entry->id] = $entry;
                    }
                }

                if ($order = $section->getEntriesOrderBy()) {
                    $this->sortSectionEntriesByEntryAttributes($entries, $order);
                }
            }

            $section->populateRelation('entries', $entries);
        }
    }

    protected function sortSectionEntriesByEntryAttributes(array &$entries, array $order): void
    {
        ArrayHelper::multisort($entries, array_keys($order), array_values($order));
    }

    protected function loadAssets(): void
    {
        $entryIds = array_map(fn (Entry $entry) => $entry->asset_count ? $entry->id : null, $this->entries);
        $entryIds = array_unique(array_filter($entryIds));

        $condition = [];

        if ($entryIds) {
            $condition[] = ['entry_id' => $entryIds, 'section_id' => null];
        }

        if ($this->sectionIdsWithAssets) {
            $condition[] = ['section_id' => $this->sectionIdsWithAssets];
        }

        if (!$condition) {
            return;
        }

        Yii::debug('Loading related assets ...');

        $this->assets = Asset::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus()
            ->andWhere(count($condition) > 1 ? ['or', ...$condition] : $condition[0])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        foreach ($this->assets as $asset) {
            $this->fileIds[] = $asset->file_id;
        }
    }

    protected function loadFiles(): void
    {
        $fileIds = array_unique($this->fileIds);
        $fileIds = array_diff($fileIds, array_keys($this->files));

        Yii::debug('Loading related files ...');

        if ($fileIds) {
            $this->files += File::find()
                ->selectSiteAttributes()
                ->replaceI18nAttributes()
                ->where(['id' => $fileIds])
                ->indexBy('id')
                ->all();
        }

        $folders = FolderCollection::getAll();

        foreach ($this->files as $file) {
            $file->populateFolderRelation($folders[$file->folder_id] ?? null);
        }
    }

    protected function populateAssetRelations(): void
    {
        foreach ($this->assets as $asset) {
            $asset->populateFileRelation($this->files[$asset->file_id] ?? null);
        }

        foreach ($this->entries as $entry) {
            $entry->populateAssetRelations($this->assets);
        }
    }
}
