<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Yii;

/**
 * Deleted one at a time and the entries recounted once at the end, so a selection spanning fifty sections of the
 * same entry costs one recalculation rather than fifty. A section that fails is reported and the rest are kept.
 */
class DeleteSections
{
    /**
     * @var list<Section>
     */
    private array $deleted = [];

    /**
     * @var list<Section>
     */
    private array $failed = [];

    /**
     * @param Section[] $sections
     */
    public function __construct(protected array $sections)
    {
    }

    public function run(): bool
    {
        $entryIds = [];
        $blockIds = [];

        foreach ($this->sections as $section) {
            $section->setIsBatch(true);

            if ($section->delete() === false) {
                $this->failed[] = $section;
                continue;
            }

            $entryIds[$section->entry_id] = $section->entry_id;
            $blockIds[] = $section->block_id;
            $this->deleted[] = $section;
        }

        if ($this->deleted) {
            $this->updateSectionCounts($entryIds);
            Section::updateBlockSectionCounts($blockIds);
        }

        return !$this->failed;
    }

    /**
     * @param int[] $entryIds
     */
    protected function updateSectionCounts(array $entryIds): void
    {
        foreach (Entry::findAll(['id' => $entryIds]) as $entry) {
            $entry->updateSectionCount();
        }
    }

    /**
     * @return list<Section>
     */
    public function getDeleted(): array
    {
        return $this->deleted;
    }

    /**
     * @return list<Section>
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    /**
     * @param Section[] $sections
     */
    public static function create(array $sections): static
    {
        $action = Yii::createObject(static::class, [$sections]);
        $action->run();

        return $action;
    }
}
