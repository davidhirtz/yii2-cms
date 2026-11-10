<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Yii;

class SyncEntryReferences
{
    /**
     * @var list<int>
     */
    protected array $entryIds = [];

    public function __construct(protected readonly ActiveRecord&CustomAttributeInterface $owner)
    {
        $this->syncReferences();
        $this->updateReferenceCounts();
    }

    protected function syncReferences(): void
    {
        $wanted = $this->owner->isDeleted() ? [] : EntryLinks::getReferenceCounts($this->owner);

        $references = EntryReference::find()
            ->where([
                'model_class' => $this->owner::class,
                'model_id' => $this->owner->getPrimaryKey(),
            ])
            ->indexBy('entry_id')
            ->all();

        // A link to an entry that is gone has no row: the foreign key would refuse it.
        $existing = $wanted
            ? array_map(intval(...), Entry::find()->select('id')->where(['id' => array_keys($wanted)])->column())
            : [];

        foreach ($wanted as $entryId => $counts) {
            if (!in_array($entryId, $existing, true)) {
                continue;
            }

            $reference = $references[$entryId] ?? null;
            unset($references[$entryId]);

            $useCount = array_sum($counts);

            if ($reference && $reference->use_count === $useCount && $this->getSortedCounts($reference) === $counts) {
                continue;
            }

            $reference ??= EntryReference::create();
            $reference->setIsBatch(true);
            $reference->setAttributes([
                'entry_id' => $entryId,
                'model_class' => $this->owner::class,
                'model_id' => $this->owner->getPrimaryKey(),
                'use_count' => $useCount,
                'attribute_counts' => $counts,
            ], false);

            $reference->save(false);
            $this->entryIds[] = $entryId;
        }

        foreach ($references as $reference) {
            $reference->setIsBatch(true);
            $reference->delete();

            $this->entryIds[] = $reference->entry_id;
        }
    }

    protected function updateReferenceCounts(): void
    {
        if (!$this->entryIds) {
            return;
        }

        $entries = Entry::find()
            ->where(['id' => array_values(array_unique($this->entryIds))])
            ->all();

        foreach ($entries as $entry) {
            $entry->updateReferenceCount();
        }
    }

    /**
     * @return array<string, int>
     */
    private function getSortedCounts(EntryReference $reference): array
    {
        $counts = array_map(intval(...), $reference->attribute_counts ?? []);
        ksort($counts);

        return $counts;
    }

    public static function create(ActiveRecord&CustomAttributeInterface $owner): void
    {
        Yii::createObject(static::class, [$owner]);
    }
}
