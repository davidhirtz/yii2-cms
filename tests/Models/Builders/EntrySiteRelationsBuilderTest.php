<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Builders;

use Hirtz\Cms\Models\builders\EntrySiteRelationsBuilder;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\FixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;

class EntrySiteRelationsBuilderTest extends TestCase
{
    use FixtureTrait;
    use ModuleTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::getModule()->enableNestedEntries = true;
        self::getModule()->enableSectionEntries = true;
    }

    public function testEnabledEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertTrue($entry->isRelationPopulated('sections'));
        self::assertTrue($entry->isRelationPopulated('assets'));
        self::assertCount(3, $entry->sections);
        self::assertCount(2, $entry->assets);

        $asset = current($entry->assets);

        self::assertTrue($asset->isRelationPopulated('entry'));
        self::assertTrue($asset->isRelationPopulated('file'));

        $section = current($entry->sections);

        self::assertTrue($section->isRelationPopulated('entry'));
        self::assertTrue($section->isRelationPopulated('assets'));
        self::assertCount(2, $section->assets);

        $asset = current($section->assets);

        self::assertTrue($asset->isRelationPopulated('section'));
        self::assertTrue($asset->isRelationPopulated('entry'));
        self::assertTrue($asset->isRelationPopulated('file'));

        self::assertCount(4, $builder->assets);
        self::assertCount(4, $builder->files);
        self::assertCount(1, $builder->entries);
    }

    public function testDraftEntry(): void
    {
        ActiveQuery::setStatus(TestEntry::STATUS_DRAFT);

        $entry = $this->getEntryFromFixture('page-enabled');

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertCount(4, $entry->sections);
        self::assertCount(2, $entry->assets);

        $section = current($entry->sections);

        self::assertCount(3, $section->assets);

        self::assertCount(6, $builder->assets);
        self::assertCount(6, $builder->files);
        self::assertCount(2, $builder->entries);

        $section = $this->getSectionFromFixture('section-blog-draft');
        $blog = $entry->sections[$section->id];

        self::assertTrue($blog->isRelationPopulated('entries'));
        self::assertCount(2, $blog->entries);

        $fixture = $this->getEntryFromFixture('post-3');
        $post = current($blog->entries);

        self::assertEquals($fixture->id, $post->id);
        self::assertTrue($post->isRelationPopulated('assets'));
        self::assertFalse($post->isRelationPopulated('sections'));
    }

    public function testDescendantEntry(): void
    {
        $entry = $this->getEntryFromFixture('post-1');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertTrue($entry->isRelationPopulated('parent'));
        self::assertCount(2, $builder->entries);
    }

    public function testDescendantEntryWithoutLoadingAncestors(): void
    {
        $entry = $this->getEntryFromFixture('post-1');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder([
            'autoloadEntryAncestors' => false,
            'entry' => $entry,
        ]);

        self::assertNull($entry->parent);
        self::assertCount(1, $builder->entries);
    }
}
