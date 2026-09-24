<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\DeleteSections;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class DeleteSectionsTest extends TestCase
{
    use UserFixtureTrait;

    private Entry $entry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entry = $this->createEntry('Page', 'page');
    }

    public function testTheSectionsAreDeletedAndTheEntryRecounted(): void
    {
        $first = $this->createSection('First');
        $second = $this->createSection('Second');
        $kept = $this->createSection('Kept');

        $action = DeleteSections::create([$first, $second]);

        self::assertCount(2, $action->getDeleted());
        self::assertSame([], $action->getFailed());

        self::assertNull(Section::findOne($first->id));
        self::assertNull(Section::findOne($second->id));
        self::assertNotNull(Section::findOne($kept->id));

        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);
    }

    /**
     * Each section is told not to touch its entry, which the action then does once per entry — so the guard in
     * `Section::afterDelete()` is what a stale count would show.
     */
    public function testABatchDeleteLeavesTheEntryCountToTheCaller(): void
    {
        $section = $this->createSection('Doomed');
        $section->setIsBatch(true);

        self::assertSame(1, $section->delete());
        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);

        $this->entry->updateSectionCount();
        self::assertSame(0, Entry::findOne($this->entry->id)->section_count);
    }

    public function testEveryEntryTheSelectionSpansIsRecounted(): void
    {
        $other = $this->createEntry('Other', 'other');

        $first = $this->createSection('First');
        $second = $this->createSection('Second', $other);

        DeleteSections::create([$first, $second]);

        self::assertSame(0, Entry::findOne($this->entry->id)->section_count);
        self::assertSame(0, Entry::findOne($other->id)->section_count);
    }

    /**
     * A section that refuses to be deleted is reported and the ones beside it are kept.
     */
    public function testAFailingSectionIsReported(): void
    {
        $deleted = $this->createSection('Deleted');

        $failing = UndeletableSection::create();
        $failing->loadDefaultValues();
        $failing->status = Section::STATUS_ENABLED;
        $failing->type = Section::TYPE_DEFAULT;
        $failing->name = 'Failing';
        $failing->populateEntryRelation($this->entry);

        self::assertTrue($failing->insert(), print_r($failing->getErrors(), true));

        $action = DeleteSections::create([$deleted, $failing]);

        self::assertCount(1, $action->getDeleted());
        self::assertCount(1, $action->getFailed());

        self::assertNotNull(Section::findOne($failing->id));
        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);
    }

    /**
     * The count is the total a section's position is read out of in the admin, so the two move together.
     */
    public function testTheSectionsLeftAreRenumbered(): void
    {
        $first = $this->createSection('First');
        $second = $this->createSection('Second');
        $third = $this->createSection('Third');
        $fourth = $this->createSection('Fourth');

        DeleteSections::create([$first, $third]);

        self::assertSame(1, Section::findOne($second->id)?->position);
        self::assertSame(2, Section::findOne($fourth->id)?->position);
    }

    public function testDeletingOneSectionRenumbersTheOthers(): void
    {
        $first = $this->createSection('First');
        $second = $this->createSection('Second');

        self::assertSame(1, $first->delete());
        self::assertSame(1, Section::findOne($second->id)?->position);
    }

    public function testMovingASectionRenumbersTheEntryItLeft(): void
    {
        $other = $this->createEntry('Other', 'other');
        $this->createSection('Resident', $other);

        $moved = $this->createSection('Moved');
        $kept = $this->createSection('Kept');

        $moved->populateEntryRelation($other);
        self::assertSame(1, $moved->update());

        self::assertSame(1, Section::findOne($kept->id)?->position);
        self::assertSame(2, Section::findOne($moved->id)?->position);
        self::assertSame(1, Entry::findOne($this->entry->id)?->section_count);
        self::assertSame(2, Entry::findOne($other->id)?->section_count);
    }

    private function createEntry(string $name, string $slug): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function createSection(string $name, ?Entry $entry = null): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = $name;
        $section->populateEntryRelation($entry ?? $this->entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }
}

class UndeletableSection extends Section
{
    #[Override]
    public function beforeDelete(): bool
    {
        return false;
    }
}
