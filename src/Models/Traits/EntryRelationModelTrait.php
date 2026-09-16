<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\EntryRelationQuery;
use Hirtz\Cms\Models\Interfaces\EntryRelationTypeInterface;

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
            ->andOnCondition([EntryRelation::tableName() . '.[[model_class]]' => $class::getModelClass()]);
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

    public function recalculateEntryCount(): static
    {
        $this->entry_count = (int)$this->getEntryRelations()->count();
        return $this;
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
        return $this->allowsEntries() ? array_values($this->entries) : [];
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
