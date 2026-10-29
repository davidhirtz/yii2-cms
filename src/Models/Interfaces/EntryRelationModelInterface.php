<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\EntryRelationQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use yii\db\ActiveRecordInterface;

/**
 * @property int $id
 * @property int $entry_count
 * @property DateTime|null $updated_at
 *
 * @property-read Entry[] $entries
 * @property-read EntryRelation[] $entryRelations
 *
 * @phpstan-require-extends ActiveRecord
 */
interface EntryRelationModelInterface extends ActiveRecordInterface, TrailModelInterface
{
    /**
     * @return class-string<EntryRelation>
     */
    public function getEntryRelationClass(): string;

    /**
     * The installation's flag and the type's answer in one place, so no caller has to remember the other.
     */
    public function allowsEntries(): bool;

    /**
     * @return EntryQuery<Entry>
     */
    public function getEntries(): EntryQuery;

    /**
     * @return EntryRelationQuery<EntryRelation>
     */
    public function getEntryRelations(): EntryRelationQuery;

    public function updateEntryCount(): int;

    /**
     * @param EntryRelation[]|null $entryRelations
     */
    public function populateEntryRelations(?array $entryRelations): void;

    /**
     * @return list<Entry>
     */
    public function getVisibleEntries(): array;

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
