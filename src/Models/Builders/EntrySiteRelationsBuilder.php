<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Builders;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Events\EntrySiteRelationsBuilderEvent;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Override;
use Yii;
use yii\base\Component;
use yii\base\Event;

class EntrySiteRelationsBuilder extends Component
{
    use ModuleTrait;

    public const string EVENT_AFTER_LOAD_ASSETS = 'afterLoadAssets';
    public const string EVENT_AFTER_LOAD_ENTRIES = 'afterLoadEntries';
    public const string EVENT_AFTER_LOAD_FILES = 'afterLoadFiles';

    public Entry $entry;

    /**
     * @var Asset[]
     */
    public array $assets = [];

    /**
     * @var Entry[]
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

    /**
     * @var int[]
     */
    public array $fileIds = [];

    /**
     * @var int[]
     */
    protected array $relatedEntryIds = [];

    /**
     * @var list<Section|Block>
     */
    protected array $modelsWithAssets = [];

    /**
     * @var array<int, Block>
     */
    protected array $blocks = [];

    /**
     * @var list<Section|Block>
     */
    protected array $modelsWithEntries = [];

    public function init(): void
    {
        $this->assets = ArrayHelper::index($this->assets, 'id');
        $this->entries = ArrayHelper::index($this->entries, 'id');
        $this->files = ArrayHelper::index($this->files, 'id');

        $this->entries[$this->entry->id] = $this->entry;

        if ($this->autoloadEntryAncestors) {
            $this->relatedEntryIds = [
                ...$this->relatedEntryIds,
                ...array_map(intval(...), $this->entry->getAncestorIds()),
            ];
        }

        $this->loadRelations();
    }

    protected function loadRelations(): void
    {
        $this->loadSections();
        $this->loadBlocks();

        $this->loadEntryRelations();
        $this->loadEntries();
        $this->trigger(self::EVENT_AFTER_LOAD_ENTRIES);

        $this->populateParentRelations();
        $this->populateEntryRelationEntries();

        $this->loadAssets();
        $this->trigger(self::EVENT_AFTER_LOAD_ASSETS);

        $this->loadFiles();
        $this->trigger(self::EVENT_AFTER_LOAD_FILES);

        $this->populateAssetRelations();
    }

    #[Override]
    public function trigger($name, ?Event $event = null): void
    {
        parent::trigger($name, $event ?? new EntrySiteRelationsBuilderEvent());
    }

    protected function loadSections(): void
    {
        if (!$this->entry->section_count) {
            return;
        }

        Yii::debug('Loading related sections ...');

        $sections = $this->entry->getSections()
            ->selectSiteAttributes()
            ->withTranslations()
            ->whereStatus()
            ->indexBy('id')
            ->all();

        foreach ($sections as $section) {
            if ($section->allowsAssets() && $section->asset_count) {
                $this->modelsWithAssets[] = $section;
            }

            if ($section->allowsEntries() && $section->entry_count) {
                $this->modelsWithEntries[] = $section;
            }
        }

        $this->entry->populateSectionRelations($sections);
    }

    /**
     * A section carrying a block renders it in place of its own content, so the block's assets and linked entries
     * are loaded with the entry's own — one extra query, and none for an entry that places no block.
     */
    protected function loadBlocks(): void
    {
        $blockIds = [];

        foreach ($this->entry->sections as $section) {
            if ($section->block_id && $section->allowsBlock()) {
                $blockIds[] = $section->block_id;
            }
        }

        if (!$blockIds) {
            return;
        }

        Yii::debug('Loading related blocks ...');

        $this->blocks = Block::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->whereStatus()
            ->andWhere(['id' => array_unique($blockIds)])
            ->indexBy('id')
            ->all();

        foreach ($this->blocks as $block) {
            if ($block->allowsAssets() && $block->asset_count) {
                $this->modelsWithAssets[] = $block;
            }

            if ($block->allowsEntries() && $block->entry_count) {
                $this->modelsWithEntries[] = $block;
            }
        }

        foreach ($this->entry->sections as $section) {
            if ($section->block_id) {
                $section->populateBlockRelation($this->blocks[$section->block_id] ?? null);
            }
        }
    }

    protected function loadEntryRelations(): void
    {
        if (!$this->modelsWithEntries) {
            return;
        }

        Yii::debug('Loading entry relations ...');

        $entryRelations = EntryRelation::find()
            ->whereModels($this->modelsWithEntries)
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $entryRelationsByModelId = [];

        foreach ($entryRelations as $entryRelation) {
            $this->relatedEntryIds[] = $entryRelation->entry_id;
            $entryRelationsByModelId[$entryRelation->model_class][$entryRelation->model_id][] = $entryRelation;
        }

        foreach ($this->modelsWithEntries as $model) {
            $model->populateEntryRelations($entryRelationsByModelId[$model::class][$model->id] ?? []);
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

    /**
     * @return EntryQuery<Entry>
     */
    protected function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->withPermalinks()
            ->whereStatus()
            ->indexBy('id');
    }

    protected function populateParentRelations(): void
    {
        if (!static::getModule()->enableNestedEntries) {
            return;
        }

        foreach ($this->entries as $entry) {
            if (!$entry->isRelationPopulated('parent') && $entry->parent_id) {
                $entry->populateParentRelation($this->entries[$entry->parent_id] ?? null);
            }
        }
    }

    protected function populateEntryRelationEntries(): void
    {
        foreach ($this->modelsWithEntries as $model) {
            $entries = [];
            $allowedTypes = $model->getEntriesTypes();

            foreach ($model->entryRelations as $entryRelation) {
                $entry = $this->entries[$entryRelation->entry_id] ?? null;

                if ($entry && (!$allowedTypes || in_array($entry->type, $allowedTypes, true))) {
                    $entries[$entry->id] = $entry;
                }
            }

            if ($order = $model->getEntriesOrderBy()) {
                $entries = $this->sortEntriesByAttributes($entries, $order);
            }

            $model->populateRelation('entries', $entries);
        }
    }

    /**
     * @param array<int, Entry> $entries
     * @param array<string, int> $order
     * @return array<int, Entry>
     */
    protected function sortEntriesByAttributes(array $entries, array $order): array
    {
        ArrayHelper::multisort($entries, array_keys($order), array_values($order));
        return $entries;
    }

    protected function loadAssets(): void
    {
        $entries = array_filter($this->entries, fn (Entry $entry): bool => (bool)$entry->asset_count);
        $models = [...array_values($entries), ...$this->modelsWithAssets];

        if (!$models) {
            return;
        }

        Yii::debug('Loading related assets ...');

        $this->assets = Asset::find()
            ->selectSiteAttributes()
            ->whereStatus()
            ->whereModels($models)
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
                ->withTranslations()
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

        foreach ($this->entry->sections as $section) {
            $section->populateAssetRelations($this->assets);
        }

        foreach ($this->blocks as $block) {
            $block->populateAssetRelations($this->assets);
        }
    }
}
