<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\CreateSectionSet;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\base\InvalidConfigException;

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
     * Each section is told not to touch the entry, which the action then does once for all of them.
     */
    public function testTheEntryCountIsUpdatedOnce(): void
    {
        $this->createSection('Existing');

        $action = CreateSectionSet::create($this->entry, $this->createSet(
            SectionTemplate::make(Section::TYPE_DEFAULT),
            SectionTemplate::make(Section::TYPE_DEFAULT),
        ));

        foreach ($action->getSections() as $section) {
            self::assertFalse($section->shouldUpdateEntryAfterSave, 'The section updated the entry itself.');
        }

        self::assertSame(3, Entry::findOne($this->entry->id)->section_count);
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

    public function testASectionTypeMayBeAnIntBackedEnum(): void
    {
        $action = CreateSectionSet::create($this->entry, $this->createSet(
            SectionTemplate::make(SectionTemplateTestEnum::Default)->attribute('name', 'Hero'),
        ));

        self::assertSame([], $action->getFailed());
        self::assertSame(Section::TYPE_DEFAULT, $action->getSections()[0]->type);
    }

    public function testAStringBackedEnumIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);
        SectionTemplate::make(SectionTemplateTestStringEnum::Default);
    }

    /**
     * A template names the type, so the class the type names is what the section has to be built as — the
     * definitions, the rules and the lifecycle hooks are all that class's.
     */
    public function testTheTypeDecidesTheSectionClass(): void
    {
        Yii::$container->set(Section::class, CreateSectionSetTestSection::class);
        DefinitionRegistry::resetClass(Section::class);

        try {
            $action = CreateSectionSet::create($this->entry, $this->createSet(
                SectionTemplate::make(CreateSectionSetTestSection::TYPE_TYPED)
                    ->attribute('name', 'Typed'),
                SectionTemplate::make(Section::TYPE_DEFAULT)
                    ->attribute('name', 'Plain'),
            ));

            self::assertSame([], $action->getFailed());

            $sections = $action->getSections();

            self::assertInstanceOf(CreateSectionSetTestTypedSection::class, $sections[0]);
            self::assertInstanceOf(CreateSectionSetTestSection::class, $sections[1]);
            self::assertNotInstanceOf(CreateSectionSetTestTypedSection::class, $sections[1]);
        } finally {
            Yii::$container->clear(Section::class);
            DefinitionRegistry::resetClass(Section::class);
        }
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

class CreateSectionSetTestSection extends Section
{
    public const int TYPE_TYPED = 2;

    #[Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_DEFAULT)
                ->name('Default'),
            SectionType::make(self::TYPE_TYPED)
                ->name('Typed')
                ->modelClass(CreateSectionSetTestTypedSection::class),
        ];
    }
}

class CreateSectionSetTestTypedSection extends CreateSectionSetTestSection
{
}

enum SectionTemplateTestEnum: int
{
    case Default = Section::TYPE_DEFAULT;
}

enum SectionTemplateTestStringEnum: string
{
    case Default = 'default';
}
