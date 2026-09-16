<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

use Hirtz\Cms\Models\Types\Traits\EntryRelationTypeTrait;

/**
 * A type of a model that links entries: which entry types it takes and how they are ordered. Implemented via
 * {@see EntryRelationTypeTrait}.
 */
interface EntryRelationTypeInterface
{
    public function allowEntries(bool $allowEntries = true): static;

    /**
     * Whether a record of this type links entries. The installation decides first — a type cannot turn on what
     * {@see EntryRelationModelInterface::allowsEntries()} reports off.
     */
    public function allowsEntries(): bool;

    /**
     * @return array<string, int>|null
     */
    public function getEntriesOrderBy(): ?array;

    /**
     * @return list<int>|null null means every entry type, an empty declaration being no restriction at all
     */
    public function getEntriesTypes(): ?array;
}
