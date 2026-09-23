<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Yii;

/**
 * Inserted one at a time rather than validated up front, so each section sees the slugs and positions of the ones
 * before it. A section that fails is reported and the rest are kept.
 */
class CreateSectionSet
{
    /**
     * @var list<Section>
     */
    private array $sections = [];

    /**
     * @var list<Section>
     */
    private array $failed = [];

    public function __construct(
        protected Entry $entry,
        protected SectionSet $set,
    ) {
    }

    public function run(): bool
    {
        foreach ($this->set->getSections() as $template) {
            $section = $this->createSection($template);

            if ($section->insert()) {
                $this->sections[] = $section;
                continue;
            }

            $this->failed[] = $section;
        }

        if ($this->sections) {
            $this->updateEntry();
        }

        return !$this->failed;
    }

    protected function createSection(SectionTemplate $template): Section
    {
        // Before the attributes: the custom attributes their values can be assigned to are the type's.
        $section = Section::instantiateByType($template->type);
        $section->populateEntryRelation($this->entry);
        $section->setIsBatch(true);
        $section->loadDefaultValues();
        $section->setAttributes($template->getAttributes(), false);

        $section->generateUniqueSlug();

        return $section;
    }

    protected function updateEntry(): void
    {
        $this->entry->updateSectionCount();
        (new UpdateBlockSectionCounts(array_map(fn (Section $section): ?int => $section->block_id, $this->sections)))->update();
    }

    /**
     * @return list<Section>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * @return list<Section>
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    public static function create(Entry $entry, SectionSet $set): static
    {
        $action = Yii::createObject(static::class, [$entry, $set]);
        $action->run();

        return $action;
    }
}
