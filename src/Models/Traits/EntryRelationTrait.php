<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use yii\db\ActiveRecord;

/**
 * `entry_id` is not declared here, since a trait `@property` the using class declares too drops its whole PHPDoc
 * scope (#125).
 *
 * @property-read Entry|null $entry
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
