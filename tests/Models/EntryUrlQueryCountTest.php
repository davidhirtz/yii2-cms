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
