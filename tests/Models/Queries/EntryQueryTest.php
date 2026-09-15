<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Queries;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class EntryQueryTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->inheritNestedCategories = true;
        $module->enableSectionEntries = true;
    }

    public function testEnabledSkipsWhatIsNotPublished(): void
    {
        $names = $this->getNames(TestEntry::find()->enabled());

        self::assertContains('Test Page – Enabled', $names);
        self::assertNotContains('Test Page – Draft', $names);
        self::assertNotContains('Test Page – Disabled', $names);
    }

    /**
     * A child of a disabled parent is not reachable, whatever its own status says.
     */
    public function testEnabledSkipsAChildOfADisabledParent(): void
    {
        $entry = $this->getEntryFromFixture('post-1');
        $entry->updateAttributes(['parent_status' => TestEntry::STATUS_DISABLED]);

        self::assertNotContains($entry->name, $this->getNames(TestEntry::find()->enabled()));
    }

    public function testMatchingSearchesTheName(): void
    {
        self::assertSame(['Test Page – Draft'], $this->getNames(TestEntry::find()->matching('Draft')));
        self::assertSame([], $this->getNames(TestEntry::find()->matching('nothing here')));
    }

    public function testMatchingWithoutASearchTermIsNoFilter(): void
    {
        $total = (int)TestEntry::find()->count();

        self::assertCount($total, TestEntry::find()->matching(null)->all());
        self::assertCount($total, TestEntry::find()->matching('  ')->all());
    }

    public function testMatchingStripsTheWildcard(): void
    {
        $sql = TestEntry::find()
            ->matching('%')
            ->createCommand()
            ->getRawSql();

        self::assertStringNotContainsString('LIKE', $sql);
    }

    public function testWhereIdNamesTheEntryTable(): void
    {
        $entry = TestEntry::find()
            ->whereId(1)
            ->one();

        self::assertSame(1, $entry?->id);
    }

    public function testWhereCategoryJoinsTheJunction(): void
    {
        $names = $this->getNames(TestEntry::find()->whereCategory($this->getCategoryFromFixture('root-1')));

        self::assertContains('Test Page – Enabled', $names);
        self::assertContains('Test Page – Draft', $names);
        self::assertNotContains('Test Page – Disabled', $names);
    }

    public function testWhereCategoryTakesTheCategorysOwnOrder(): void
    {
        $names = $this->getNames(TestEntry::find()->whereCategory($this->getCategoryFromFixture('root-1')));

        // the fixture puts the enabled page at position 1 and the draft at 2
        self::assertSame(['Test Page – Enabled', 'Test Page – Draft'], $names);
    }

    public function testWhereCategoryAcceptsAPlainId(): void
    {
        $names = $this->getNames(TestEntry::find()->whereCategory(2));

        self::assertSame(['Test Page – Disabled'], $names);
    }

    /**
     * Several categories are ANDed, so each one needs its own alias for the junction.
     */
    public function testWhereCategoriesAsksForAllOfThem(): void
    {
        self::assertSame(
            ['Test Page – Enabled'],
            $this->getNames(TestEntry::find()->whereCategories([1, 3]))
        );

        self::assertSame([], $this->getNames(TestEntry::find()->whereCategories([2, 3])));
    }

    public function testWhereCategoryEagerLoadsTheJunctionFromTheSameRow(): void
    {
        $entries = TestEntry::find()
            ->whereCategory($this->getCategoryFromFixture('root-1'), true)
            ->all();

        self::assertNotEmpty($entries);

        $queries = $this->countQueries(function () use ($entries): void {
            foreach ($entries as $entry) {
                self::assertSame($entry->id, $entry->entryCategory->entry_id);
            }
        });

        self::assertSame(0, $queries);
    }

    public function testWhereSectionJoinsTheLinkedEntries(): void
    {
        $section = Section::findOne(3);
        $names = $this->getNames(TestEntry::find()->whereSection($section));

        self::assertContains('Test Page – Enabled', $names);
        self::assertNotContains('Test Page – Disabled', $names);
    }

    public function testWhereSectionCanAlsoBeALeftJoin(): void
    {
        $section = Section::findOne(3);

        $inner = $this->getNames(TestEntry::find()->whereSection($section));
        $left = $this->getNames(TestEntry::find()->whereSection($section, 'LEFT JOIN'));

        self::assertGreaterThan(count($inner), count($left));
    }

    public function testWhereUriResolvesTheEntryAndItsPermalinkInOneQuery(): void
    {
        $queries = $this->countQueries(function (): void {
            $entry = TestEntry::find()
                ->whereUri('test-1')
                ->one();

            self::assertSame(1, $entry?->id);
            self::assertSame('test-1', $entry->getPermalink()?->uri);
        });

        self::assertSame(1, $queries);
    }

    public function testWhereUriIgnoresTheSurroundingSlashes(): void
    {
        self::assertSame(1, TestEntry::find()->whereUri('/test-1/')->one()?->id);
    }

    public function testWhereUriFindsNothingForAPathThatIsNotThere(): void
    {
        self::assertNull(TestEntry::find()->whereUri('nowhere')->one());
    }

    public function testWhereNotUriExcludesTheEntryBehindThePath(): void
    {
        $ids = array_map(
            fn (Entry $entry): int => $entry->id,
            TestEntry::find()->whereNotUri('test-1')->all()
        );

        self::assertNotContains(1, $ids);
        self::assertNotEmpty($ids);
    }

    public function testWhereNotUriWithoutAPathIsNoFilter(): void
    {
        $total = (int)TestEntry::find()->count();

        self::assertCount($total, TestEntry::find()->whereNotUri(null)->all());
        self::assertCount($total, TestEntry::find()->whereNotUri('')->all());
    }

    public function testSelectSiteAttributesLeavesOutWhatTheFrontendDoesNotNeed(): void
    {
        $sql = TestEntry::find()
            ->selectSiteAttributes()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('`entry`.`name`', $sql);
        self::assertStringNotContainsString('`entry`.`updated_by_user_id`', $sql);
        self::assertStringNotContainsString('`entry`.`created_at`', $sql);
    }

    public function testSelectSitemapAttributesIsScopedToTheTenant(): void
    {
        $sql = TestEntry::find()
            ->selectSitemapAttributes()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('`entry`.`updated_at`', $sql);
        self::assertStringNotContainsString('`entry`.`name`', $sql);
        self::assertStringContainsString('tenant_id', $sql);
    }

    /**
     * @param EntryQuery<covariant Entry> $query
     * @return list<string>
     */
    private function getNames(EntryQuery $query): array
    {
        return array_values(array_map(
            fn (Entry $entry): string => (string)$entry->name,
            $query->all()
        ));
    }
}
