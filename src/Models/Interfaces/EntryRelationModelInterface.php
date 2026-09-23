<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\EntryRelationQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use yii\db\ActiveRecordInterface;

/**
 * A model that links entries.
 *
 * @property int $id
 * @property int $entry_count
 * @property DateTime|null $updated_at
 *
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read EntryRelation[] $entryRelations {@see static::getEntryRelations()}
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
     * Whether this record links entries — the installation's flag and the type's own answer, in one place so no
     * caller has to remember the other.
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
     * @return list<int>|null null means every entry type, an empty declaration being no restriction at all
     */
    public function getEntriesTypes(): ?array;
}
