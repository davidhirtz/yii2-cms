<?php

namespace davidhirtz\yii2\cms\models\builders;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\BaseObject;

class EntrySiteRelationsBuilder extends BaseObject
{
    use ModuleTrait;

    public Entry $entry;
    public array $assets = [];
    public array $entries = [];

    protected array $relatedEntryIds = [];
    protected array $sectionIdsWithAssets = [];
    protected array $sectionIdsWithEntries = [];

    public function init(): void
    {
        ArrayHelper::index($this->entries, 'id');
        $this->entries[$this->entry->id] = $this->entry;

        $this->loadRelations();
    }

    protected function loadRelations(): void
    {
        $this->loadSections();

        $this->loadSectionEntries();
        $this->populateSectionEntryRelations();

        $this->loadEntries();

        $this->loadAssets();
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

        Yii::debug('Loading entries for sections ...');

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

            $this->entries = [
                ...$this->entries,
                ...Entry::find()
                    ->selectSiteAttributes()
                    ->replaceI18nAttributes()
                    ->whereStatus()
                    ->andWhere(['id' => $entryIds])
                    ->indexBy('id')
                    ->all()
            ];
        }
    }

    protected function populateSectionEntryRelations(): void
    {
        if (!$this->entries || !static::getModule()->enableSectionEntries) {
            return;
        }

        foreach ($this->entry->sections as $section) {
            $entries = array_map(fn(SectionEntry $sectionEntry) => $this->entries[$sectionEntry->id] ?? null, $section->sectionEntries);
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
            ->withFiles()
            ->whereStatus()
            ->andWhere(count($condition) > 1 ? ['or', ...$condition] : $condition[0])
            ->all();
    }

    protected function populateAssetRelations(): void
    {
        foreach ($this->entries as $entry) {
            $entry->populateAssetRelations($this->assets);
        }
    }
}
