<?php

namespace davidhirtz\yii2\cms\models\builders;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\collections\FolderCollection;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\BaseObject;

class EntrySiteRelationsBuilder extends BaseObject
{
    use ModuleTrait;

    public Entry $entry;

    /** @var Asset[] */
    public array $assets = [];

    /** @var Entry[] */
    public array $entries = [];

    /** @var File[] */
    public array $files = [];

    protected array $fileIds = [];
    protected array $relatedEntryIds = [];
    protected array $sectionIdsWithAssets = [];
    protected array $sectionIdsWithEntries = [];

    public function init(): void
    {
        ArrayHelper::index($this->entries, 'id');
        ArrayHelper::index($this->files, 'id');

        $this->entries[$this->entry->id] = $this->entry;

        $this->loadRelations();
    }

    protected function loadRelations(): void
    {
        $this->loadSections();

        $this->loadSectionEntries();
        $this->loadEntries();

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
            if (static::getModule()->enableSectionAssets && $section->asset_count) {
                $this->sectionIdsWithAssets[] = $section->id;
            }

            if (static::getModule()->enableSectionEntries && $section->entry_count) {
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

            $this->entries += Entry::find()
                ->selectSiteAttributes()
                ->replaceI18nAttributes()
                ->whereStatus()
                ->andWhere(['id' => $entryIds])
                ->indexBy('id')
                ->all();
        }
    }

    protected function populateSectionEntryRelations(): void
    {
        if (!$this->entries || !static::getModule()->enableSectionEntries) {
            return;
        }

        foreach ($this->entry->sections as $section) {
            $entries = array_map(fn(SectionEntry $sectionEntry) => $this->entries[$sectionEntry->entry_id] ?? null, $section->sectionEntries);
            $section->populateRelation('entries', array_filter($entries));
        }
    }

    protected function loadAssets(): void
    {
        $entryIds = array_map(fn(Entry $entry) => $entry->asset_count ? $entry->id : null, $this->entries);
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
