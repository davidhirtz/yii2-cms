<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Console\Controllers\EntryController;
use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

/**
 * `Entry::$relation_count` counts the sections and blocks listing the entry, batch paths included.
 */
class EntryRelationCountTest extends TestCase
{
    use CmsFixtureTrait;

    private const int SECTION_ID = 3;

    public function testAnInsertAndADeleteCountOnTheListedEntry(): void
    {
        $count = $this->countRelations(5);

        $relation = $this->createRelation(5);
        self::assertSame($count + 1, $this->getRelationCount(5));

        self::assertSame(1, $relation->delete());
        self::assertSame($count, $this->getRelationCount(5));
    }

    public function testDuplicatingAndDeletingASectionCountsEachListedEntry(): void
    {
        $this->createRelation(4);
        $this->createRelation(5);

        $counts = [4 => $this->countRelations(4), 5 => $this->countRelations(5)];

        $duplicate = DuplicateSection::create([TestSection::findOne(self::SECTION_ID)]);
        self::assertFalse($duplicate->getIsNewRecord(), implode(' ', $duplicate->getErrorSummary(true)));

        self::assertSame($counts[4] + 1, $this->getRelationCount(4));
        self::assertSame($counts[5] + 1, $this->getRelationCount(5));

        self::assertSame(1, $duplicate->delete());

        self::assertSame($counts[4], $this->getRelationCount(4));
        self::assertSame($counts[5], $this->getRelationCount(5));
    }

    public function testTheReferencesCommandCorrectsTheCount(): void
    {
        Entry::updateAll(['relation_count' => 99], ['id' => [4, 5]]);

        $controller = Yii::createObject(StdOutBufferEntryController::class, ['entry', Yii::$app]);
        $controller->actionReferences();

        self::assertSame($this->countRelations(4), $this->getRelationCount(4));
        self::assertSame($this->countRelations(5), $this->getRelationCount(5));
        self::assertStringContainsString('relation counts were corrected', $controller->flushStdOutBuffer());
    }

    private function getRelationCount(int $entryId): ?int
    {
        return Entry::findOne($entryId)?->relation_count;
    }

    private function countRelations(int $entryId): int
    {
        return (int)EntryRelation::find()->where(['entry_id' => $entryId])->count();
    }

    private function createRelation(int $entryId): SectionEntry
    {
        $relation = SectionEntry::create();
        $relation->populateModelRelation(Section::findOne(self::SECTION_ID) ?? self::fail('No section.'));
        $relation->populateEntryRelation(Entry::findOne($entryId));
        self::assertTrue($relation->insert(), implode(' ', $relation->getErrorSummary(true)));

        return $relation;
    }
}

class StdOutBufferEntryController extends EntryController
{
    use StdOutBufferControllerTrait;
}
