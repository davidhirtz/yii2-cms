<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\CreateSectionSet;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class CreateSectionSetTest extends TestCase
{
    use UserFixtureTrait;

    private Entry $entry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entry = $this->createEntry();
    }

    public function testTheSectionsAreCreatedInOrderWithTheirDefaults(): void
    {
        $action = CreateSectionSet::create($this->entry, $this->createSet(
            SectionTemplate::make(Section::TYPE_DEFAULT)
                ->attribute('name', 'Hero'),
            SectionTemplate::make(Section::TYPE_DEFAULT)
                ->attributes([
                    'name' => 'Text',
                    'slug' => 'Text Anchor',
                    'status' => Section::STATUS_DISABLED,
                ]),
        ));

        self::assertSame([], $action->getFailed());

        $sections = $action->getSections();

        self::assertCount(2, $sections);
        self::assertSame('Hero', $sections[0]->name);
        self::assertSame(Section::STATUS_ENABLED, $sections[0]->status);

        self::assertSame('Text', $sections[1]->name);
        self::assertSame(Section::STATUS_DISABLED, $sections[1]->status);
        self::assertSame('text-anchor', $sections[1]->slug);

        self::assertLessThan($sections[1]->position, $sections[0]->position);
    }

    /**
     * The sections are inserted as a batch, so the entry is touched once after the last of them.
     */
    public function testTheEntryCountIsUpdatedOnce(): void
    {
        $this->createSection('Existing');

        $action = CreateSectionSet::create($this->entry, $this->createSet(
            SectionTemplate::make(Section::TYPE_DEFAULT),
            SectionTemplate::make(Section::TYPE_DEFAULT),
        ));

        $sections = $action->getSections();
        $entry = Entry::findOne($this->entry->id);

        self::assertSame(3, $entry->section_count);
        self::assertSame(
            $sections[1]->updated_at->getTimestamp(),
            $entry->updated_at->getTimestamp()
        );
    }

    /**
     * A section the project declared with an invalid value is reported, the ones beside it are kept.
     */
    public function testAFailingSectionIsReported(): void
    {
        $action = CreateSectionSet::create($this->entry, $this->createSet(
            SectionTemplate::make(Section::TYPE_DEFAULT)
                ->attribute('name', 'Valid'),
            SectionTemplate::make(Section::TYPE_DEFAULT)
                ->attribute('status', 99),
        ));

        self::assertCount(1, $action->getSections());
        self::assertCount(1, $action->getFailed());
        self::assertArrayHasKey('status', $action->getFailed()[0]->getErrors());

        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);
    }

    private function createSet(SectionTemplate ...$sections): SectionSet
    {
        return SectionSet::make(1)
            ->name('Landing page')
            ->sections(...$sections);
    }

    private function createEntry(): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = 'Page';
        $entry->slug = 'page';

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function createSection(string $name): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = $name;
        $section->populateEntryRelation($this->entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }
}
