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
            $entry->getUrl();
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
     * @param list<int> $ids
     * @return Entry[]
     */
    protected function findEntries(array $ids): array
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
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
