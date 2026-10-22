<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\EntryRelationQuery;
use Hirtz\Cms\Models\Interfaces\EntryRelationTypeInterface;
use Hirtz\Skeleton\Helpers\ArrayHelper;

/**
 * @mixin EntryRelationModelInterface
 */
trait EntryRelationModelTrait
{
    /**
     * The subclass scope lives in its `find()`, which a `joinWith()` would move into the outer `WHERE` and turn the
     * left join into an inner one.
     *
     * @return EntryRelationQuery<EntryRelation>
     */
    public function getEntryRelations(): EntryRelationQuery
    {
        $class = $this->getEntryRelationClass();

        /** @var EntryRelationQuery<EntryRelation> */
        return $this->hasMany($class, ['model_id' => 'id'])
            ->andOnCondition([EntryRelation::tableName() . '.[[model_class]]' => $class::getModelClass()])
            ->orderBy([EntryRelation::tableName() . '.[[position]]' => SORT_ASC]);
    }

    /**
     * @return EntryQuery<Entry>
     */
    public function getEntries(): EntryQuery
    {
        /** @var EntryQuery<Entry> $relation */
        $relation = $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryRelations');

        return $relation;
    }

    public function updateEntryCount(): int
    {
        return $this->updateDenormalizedAttributes([
            'entry_count' => (int)$this->getEntryRelations()->count(),
        ]);
    }

    /**
     * @param EntryRelation[]|null $entryRelations
     */
    public function populateEntryRelations(?array $entryRelations): void
    {
        foreach ($entryRelations ?? [] as $entryRelation) {
            $entryRelation->populateModelRelation($this);
        }

        $this->populateRelation('entryRelations', $entryRelations ?? []);
    }

    /**
     * @return list<Entry>
     */
    public function getVisibleEntries(): array
    {
        return $this->allowsEntries() ? $this->sortByEntriesOrder(array_values($this->entries)) : [];
    }

    /**
     * The type's order, which a preload applies too, so a section shows one order however it was loaded.
     *
     * @param list<Entry> $entries
     * @return list<Entry>
     */
    protected function sortByEntriesOrder(array $entries): array
    {
        if ($order = $this->getEntriesOrderBy()) {
            ArrayHelper::multisort($entries, array_keys($order), array_values($order));
        }

        return array_values($entries);
    }

    /**
     * @return array<string, int>|null
     */
    public function getEntriesOrderBy(): ?array
    {
        $type = $this->getType();
        return $type instanceof EntryRelationTypeInterface ? $type->getEntriesOrderBy() : null;
    }

    /**
     * @return list<int>|null
     */
    public function getEntriesTypes(): ?array
    {
        $type = $this->getType();
        return $type instanceof EntryRelationTypeInterface ? $type->getEntriesTypes() : null;
    }

    protected function typeAllowsEntries(): bool
    {
        $type = $this->getType();
        return !$type instanceof EntryRelationTypeInterface || $type->allowsEntries();
    }

    /**
     * A section or block is deleted through the model layer, but `entry_relation.model_id` can carry no foreign
     * key of its own — it points at two tables — so the rows have to go by hand.
     */
    protected function deleteEntryRelations(): void
    {
        foreach ($this->getEntryRelations()->all() as $entryRelation) {
            $entryRelation->populateModelRelation($this);
            $entryRelation->setIsBatch(true);
            $entryRelation->delete();
        }
    }
}
