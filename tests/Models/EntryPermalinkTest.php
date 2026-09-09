<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

/**
 * While the slug columns are still the source of truth, a permalink must always agree with
 * {@see TestEntry::getFormattedSlug()}. Every case here asserts that invariant.
 */
class EntryPermalinkTest extends TestCase
{
    public function testPermalinkIsWrittenOnInsert(): void
    {
        $entry = $this->createEntry('test-entry');

        self::assertPermalinkMatchesEntry($entry);
        self::assertSame('test-entry', $entry->getPermalink()->uri);
        self::assertSame('test-entry', $entry->getPermalink()->slug);
    }

    public function testPermalinkPointsAtTheResolvedModelClass(): void
    {
        $entry = $this->createEntry('test-entry');
        $permalink = $entry->getPermalink();

        self::assertSame(TestEntry::class, $permalink->model);
        self::assertSame($entry->id, $permalink->model_id);
        self::assertTrue($permalink->isModel(TestEntry::class));
    }

    public function testPermalinkFollowsARename(): void
    {
        $entry = $this->createEntry('test-entry');

        $entry->slug = 'renamed';
        self::assertTrue($entry->update() !== false);

        self::assertPermalinkMatchesEntry($entry);
        self::assertSame('renamed', $entry->getPermalink()->uri);
    }

    public function testChildPermalinkIncludesTheParentPath(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);

        self::assertPermalinkMatchesEntry($child);
        self::assertSame('parent/child', $child->getPermalink()->uri);
    }

    /**
     * The cascade in {@see TestEntry::afterSave()} re-saves every descendant, so their permalinks have to follow a
     * parent rename without any separate bookkeeping.
     */
    public function testRenamingAParentRewritesDescendantPermalinks(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);
        $grandchild = $this->createEntry('grandchild', $child);

        $parent->refresh();
        $parent->slug = 'renamed';
        self::assertTrue($parent->update() !== false);

        self::assertSame('renamed/child', $this->findPermalinkUri($child));
        self::assertSame('renamed/child/grandchild', $this->findPermalinkUri($grandchild));
    }

    public function testMovingAnEntryRewritesDescendantPermalinks(): void
    {
        $first = $this->createEntry('first');
        $second = $this->createEntry('second');
        $child = $this->createEntry('child', $first);
        $grandchild = $this->createEntry('grandchild', $child);

        $child->refresh();
        $child->parent_id = $second->id;
        self::assertTrue($child->update() !== false);

        self::assertSame('second/child', $this->findPermalinkUri($child));
        self::assertSame('second/child/grandchild', $this->findPermalinkUri($grandchild));
    }

    public function testPermalinkIsDeletedWithTheEntry(): void
    {
        $entry = $this->createEntry('test-entry');
        $id = $entry->id;

        self::assertTrue($entry->delete() !== false);

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(TestEntry::class, $id)
            ->count());
    }

    public function testDeletingAParentDeletesDescendantPermalinks(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);

        $parent->refresh();
        self::assertTrue($parent->delete() !== false);

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(TestEntry::class, $child->id)
            ->count());
    }

    /**
     * Two entries may share a leaf as long as their parents differ, so the unique index must be on the full path.
     */
    public function testSameSlugUnderDifferentParents(): void
    {
        $first = $this->createEntry('first');
        $second = $this->createEntry('second');

        $one = $this->createEntry('child', $first);
        $two = $this->createEntry('child', $second);

        self::assertSame('first/child', $this->findPermalinkUri($one));
        self::assertSame('second/child', $this->findPermalinkUri($two));
    }

    protected function findPermalinkUri(TestEntry $entry): ?string
    {
        $permalink = Permalink::find()
            ->whereModel(TestEntry::class, $entry->id)
            ->whereLanguage()
            ->one();

        return $permalink?->uri;
    }

    protected static function assertPermalinkMatchesEntry(TestEntry $entry): void
    {
        foreach ($entry->getPermalinkLanguages() as $language) {
            $permalink = $entry->getPermalink($language);
            self::assertNotNull($permalink, "Missing permalink for language $language.");
            self::assertSame($entry->getFormattedSlug($language), $permalink->uri);
        }
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

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableNestedEntries = true;
    }
}
