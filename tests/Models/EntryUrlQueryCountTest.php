<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

/**
 * Building a URL must not walk up the tree. The permalink already holds the full path and the site queries eager
 * load it; composing the path from the parent instead costs a query per row, and another per level of nesting.
 */
class EntryUrlQueryCountTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableNestedEntries = true;
    }

    public function testBuildingUrlsDoesNotLoadTheParent(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);
        $grandchild = $this->createEntry('grandchild', $child);

        $entries = $this->findEntries([$parent->id, $child->id, $grandchild->id]);
        self::assertCount(3, $entries);

        foreach ($entries as $entry) {
            $entry->getFormattedSlug();
            $entry->getRoute();
        }

        foreach ($entries as $entry) {
            self::assertFalse(
                $entry->isRelationPopulated('parent'),
                "Building the URL of '{$entry->slug}' loaded its parent."
            );
        }
    }

    public function testEagerLoadedEntriesResolveTheirFullPath(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);
        $grandchild = $this->createEntry('grandchild', $child);

        $entries = [];

        foreach ($this->findEntries([$parent->id, $child->id, $grandchild->id]) as $entry) {
            $entries[$entry->slug] = $entry;
        }

        self::assertSame('parent', $entries['parent']->getFormattedSlug());
        self::assertSame('parent/child', $entries['child']->getFormattedSlug());
        self::assertSame('parent/child/grandchild', $entries['grandchild']->getFormattedSlug());
    }

    /**
     * A slug lives in a permalink record, but loading an entry must not fetch it: the relation stays unloaded until
     * the slug (or a route derived from it) is actually read.
     */
    public function testLoadingAnEntryDoesNotLoadPermalinks(): void
    {
        $created = $this->createEntry('test-entry');

        $entry = TestEntry::findOne($created->id);

        self::assertFalse($entry->isRelationPopulated('permalinks'), 'Loading the entry fetched its permalinks.');

        // Touching an unrelated attribute must not trigger it either.
        self::assertSame('Test-entry', $entry->name);
        self::assertFalse($entry->isRelationPopulated('permalinks'));

        // Reading the slug materialises it from the permalink.
        self::assertSame('test-entry', $entry->slug);
        self::assertTrue($entry->isRelationPopulated('permalinks'));
    }

    /**
     * The lookup joins the permalink, so the record it matched comes back in the same row rather than through a
     * second query for the relation.
     */
    public function testFindingAnEntryByUriReadsThePermalinkInTheSameQuery(): void
    {
        $created = $this->createEntry('test-entry');
        $entry = null;
        $slug = null;

        $count = $this->countQueries(function () use (&$entry, &$slug): void {
            $entry = Entry::find()->whereUri('test-entry')->one();
            $slug = $entry?->getFormattedSlug();
        });

        self::assertSame(1, $count);
        self::assertSame('test-entry', $slug);
        self::assertSame($created->id, $entry->id);
        self::assertFalse($entry->isRelationPopulated('permalinks'), 'The matched record must not stand in for the relation.');

        // The language-agnostic record resolves under every language without touching the relation.
        self::assertSame($entry->getPermalink(), $entry->getPermalink('de'));
        self::assertFalse($entry->isRelationPopulated('permalinks'));

        // Anything that needs the full set still loads it.
        self::assertCount(1, $entry->permalinks);
        self::assertTrue($entry->isRelationPopulated('permalinks'));
    }

    /**
     * The joined table has an `id` too; without a select of its own the query would read the permalink's.
     */
    public function testFindingAnEntryByUriReturnsTheEntryId(): void
    {
        $created = $this->createEntry('test-entry');
        $permalink = $created->getPermalink();

        self::assertNotSame($created->id, $permalink->id, 'The ids coincide, so the test cannot tell them apart.');

        $entry = Entry::find()->whereUri('test-entry')->one();

        self::assertSame($created->id, $entry->id);
        self::assertSame($permalink->id, $entry->getPermalink()->id);
    }

    /**
     * The entry validates the permalink itself, so writing it must not check uniqueness again, and the relation
     * it loaded for that must serve the save too: load, check, write, trail.
     */
    public function testRenameRunsOneUniquenessCheck(): void
    {
        $entry = TestEntry::findOne($this->createEntry('test-entry')->id);

        $count = $this->countQueries(function () use ($entry): void {
            $entry->slug = 'renamed';
            $entry->update();
        });

        self::assertSame(4, $count);
        self::assertSame('renamed', TestEntry::findOne($entry->id)->slug);
    }

    public function testUpdateWithoutSlugChangeLoadsThePermalinksOnce(): void
    {
        $entry = TestEntry::findOne($this->createEntry('test-entry')->id);

        $count = $this->countQueries(function () use ($entry): void {
            $entry->name = 'Renamed';
            $entry->update();
        });

        // Load, update, trail.
        self::assertSame(3, $count);
    }

    /**
     * @param list<int> $ids
     * @return Entry[]
     */
    protected function findEntries(array $ids): array
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->withPermalinks()
            ->andWhere(['id' => $ids])
            ->all();
    }

    protected function createEntry(string $slug, ?TestEntry $parent = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = ucfirst($slug);
        $entry->slug = $slug;
        $entry->parent_id = $parent?->id;

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
