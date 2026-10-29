<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

interface EntryRelationTypeInterface
{
    public function allowEntries(bool $allowEntries = true): static;

    /**
     * The installation decides first: a type cannot turn on what
     * {@see EntryRelationModelInterface::allowsEntries()} reports off.
     */
    public function allowsEntries(): bool;

    /**
     * @return array<string, int>|null
     */
    public function getEntriesOrderBy(): ?array;

    /**
     * `null` is every entry type, as is an empty declaration.
     *
     * @return list<int>|null
     */
    public function getEntriesTypes(): ?array;
}
