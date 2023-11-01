<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;

/**
 * @property int|null $entry_id
 * @property-read Entry $entry {@see static::getEntry()}
 */
trait EntryRelationTrait
{
    public function getEntry(): EntryQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    public function populateEntryRelation(?Entry $entry): void
    {
        $this->populateRelation('entry', $entry);
        $this->entry_id = $entry?->id;
    }
}