<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use yii\db\ActiveRecord;

/**
 * `entry_id` is deliberately not declared here: every using model declares it itself, and a trait `@property` is
 * flattened into the using class, so a second declaration of the same name silently drops that class's whole
 * PHPDoc scope rather than being reported (monorepo issue #125).
 *
 * @property-read Entry|null $entry {@see static::getEntry()}
 *
 * @mixin ActiveRecord
 */
trait EntryRelationTrait
{
    /**
     * @return EntryQuery<Entry>
     */
    public function getEntry(): EntryQuery
    {
        /** @var EntryQuery<Entry> $relation */
        $relation = $this->hasOne(Entry::class, ['id' => 'entry_id']);
        return $relation;
    }

    public function populateEntryRelation(?Entry $entry): void
    {
        $this->populateRelation('entry', $entry);
        $this->entry_id = $entry?->id;
    }
}
