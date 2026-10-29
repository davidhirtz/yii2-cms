<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Yii;

/**
 * The entries are recounted once at the end, and a section that fails is reported while the rest are kept.
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
            (new UpdateBlockSectionCounts($blockIds))->update();
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
