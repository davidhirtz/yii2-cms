<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\unit\models\builders;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\builders\EntrySiteRelationsBuilder;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use davidhirtz\yii2\cms\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

class EntrySiteRelationsBuilderTest extends Unit
{
    use CmsFixturesTrait;

    protected UnitTester $tester;

    public function testEnabledEntry(): void
    {
        $entry = $this->tester->grabEntryFixture('page-enabled');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertTrue($entry->isRelationPopulated('sections'));
        self::assertTrue($entry->isRelationPopulated('assets'));
        self::assertEquals(3, count($entry->sections));
        self::assertEquals(2, count($entry->assets));

        $asset = current($entry->assets);

        self::assertTrue($asset->isRelationPopulated('entry'));
        self::assertTrue($asset->isRelationPopulated('file'));

        $section = current($entry->sections);

        self::assertTrue($section->isRelationPopulated('entry'));
        self::assertTrue($section->isRelationPopulated('assets'));
        self::assertEquals(2, count($section->assets));

        $asset = current($section->assets);

        self::assertTrue($asset->isRelationPopulated('section'));
        self::assertTrue($asset->isRelationPopulated('entry'));
        self::assertTrue($asset->isRelationPopulated('file'));

        self::assertEquals(4, count($builder->assets));
        self::assertEquals(4, count($builder->files));
        self::assertEquals(1, count($builder->entries));
    }

    public function testDraftEntry(): void
    {
        $entry = $this->tester->grabEntryFixture('page-enabled');
        ActiveQuery::setStatus(TestEntry::STATUS_DRAFT);

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertEquals(4, count($entry->sections));
        self::assertEquals(2, count($entry->assets));

        $section = current($entry->sections);

        self::assertEquals(3, count($section->assets));

        self::assertEquals(6, count($builder->assets));
        self::assertEquals(6, count($builder->files));
        self::assertEquals(2, count($builder->entries));

        $section = $this->tester->grabSectionFixture('section-blog-draft');
        $blog = $entry->sections[$section->id];

        self::assertTrue($blog->isRelationPopulated('entries'));
        self::assertEquals(2, count($blog->entries));

        $fixture = $this->tester->grabEntryFixture('post-3');
        $post = current($blog->entries);

        self::assertEquals($fixture->id, $post->id);
        self::assertTrue($post->isRelationPopulated('assets'));
        self::assertFalse($post->isRelationPopulated('sections'));
    }

    public function testDescendantEntry(): void
    {
        $entry = $this->tester->grabEntryFixture('post-1');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder(['entry' => $entry]);

        self::assertTrue($entry->isRelationPopulated('parent'));
        self::assertEquals(2, count($builder->entries));
    }

    public function testDescendantEntryWithoutLoadingAncestors()
    {
        $entry = $this->tester->grabEntryFixture('post-1');
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        $builder = new EntrySiteRelationsBuilder([
            'autoloadEntryAncestors' => false,
            'entry' => $entry,
        ]);

        self::assertNull($entry->parent);
        self::assertEquals(1, count($builder->entries));
    }
}
