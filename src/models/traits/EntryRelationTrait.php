<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;

/**
 * @property int|null $entry_id
 * @property-read Entry|null $entry {@see static::getEntry()}
 */
trait EntryRelationTrait
{
    public function getEntry(): EntryQuery
    {
        /** @var EntryQuery $relation */
        $relation = $this->hasOne(Entry::class, ['id' => 'entry_id']);
        return $relation;
    }

    public function populateEntryRelation(?Entry $entry): void
    {
        $this->populateRelation('entry', $entry);
        $this->entry_id = $entry?->id;
    }
}
