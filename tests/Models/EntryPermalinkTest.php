<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
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

        self::assertSame(Entry::class, $permalink->model_class);
        self::assertSame($entry->id, $permalink->model_id);
        self::assertTrue($permalink->isModel(Entry::class));
    }

    /**
     * Renaming touches no column on the entry itself, so Yii reports no affected rows. Callers read the return
     * value as success, so it has to account for the permalink that was rewritten.
     */
    public function testSlugOnlyUpdateReportsAnAffectedRow(): void
    {
        $entry = $this->createEntry('before');

        $entry->slug = 'after';

        self::assertSame(1, $entry->update());
        self::assertSame('after', $this->findPermalinkUri($entry));
    }

    public function testUnchangedUpdateReportsNoAffectedRow(): void
    {
        $entry = $this->createEntry('unchanged');

        self::assertSame(0, $entry->update());
    }

    public function testPermalinkFollowsARename(): void
    {
        $entry = $this->createEntry('test-entry');

        $entry->slug = 'renamed';
        self::assertNotFalse($entry->update());

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
        self::assertNotFalse($parent->update());

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
        self::assertNotFalse($child->update());

        self::assertSame('second/child', $this->findPermalinkUri($child));
        self::assertSame('second/child/grandchild', $this->findPermalinkUri($grandchild));
    }

    public function testPermalinkIsDeletedWithTheEntry(): void
    {
        $entry = $this->createEntry('test-entry');
        $id = $entry->id;

        self::assertNotFalse($entry->delete());

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(Entry::class, $id)
            ->count());
    }

    public function testDeletingAParentDeletesDescendantPermalinks(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);

        $parent->refresh();
        self::assertNotFalse($parent->delete());
        self::assertNull(TestEntry::findOne($child->id), 'The child entry was not deleted.');

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(Entry::class, $child->id)
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

    public function testRenameRecordsARedirect(): void
    {
        $entry = $this->createEntryWithSection('test-entry');

        $entry->slug = 'renamed';
        self::assertNotFalse($entry->update());

        self::assertSame('renamed', $this->findRedirectTarget('test-entry'));
    }

    /**
     * `RedirectBehavior` only produced these because every descendant happened to be re-saved. Recording them from
     * the permalink action makes it explicit.
     */
    public function testRenamingAParentRecordsRedirectsForDescendants(): void
    {
        $parent = $this->createEntryWithSection('parent');
        $this->createEntryWithSection('child', $parent);

        $parent->refresh();
        $parent->slug = 'renamed';
        self::assertNotFalse($parent->update());

        self::assertSame('renamed', $this->findRedirectTarget('parent'));
        self::assertSame('renamed/child', $this->findRedirectTarget('parent/child'));
    }

    /**
     * Two renames in a row must leave one hop, not a chain.
     */
    public function testRepeatedRenamesDoNotChainRedirects(): void
    {
        $entry = $this->createEntryWithSection('first');

        $entry->slug = 'second';
        self::assertNotFalse($entry->update());

        $entry->refresh();
        $entry->slug = 'third';
        self::assertNotFalse($entry->update());

        self::assertSame('third', $this->findRedirectTarget('first'));
        self::assertSame('third', $this->findRedirectTarget('second'));
    }

    public function testDeletingAnEntryRemovesRedirectsPointingAtIt(): void
    {
        $entry = $this->createEntryWithSection('first');

        $entry->slug = 'second';
        self::assertNotFalse($entry->update());
        self::assertSame('second', $this->findRedirectTarget('first'));

        $entry->refresh();
        self::assertNotFalse($entry->delete());

        self::assertNull($this->findRedirectTarget('first'));
    }

    /**
     * The slug is no longer a column on the entry, so nothing reports it as changed unless the model does. Without
     * that, renaming an entry left no trace in the trail at all.
     */
    public function testRenameIsRecordedInTheTrail(): void
    {
        $entry = $this->createEntry('before');

        $entry->slug = 'after';
        self::assertNotFalse($entry->update());

        $data = $this->findTrailData($entry, Trail::TYPE_UPDATE);

        self::assertNotEmpty($data, 'No trail record was written for the rename.');
        self::assertSame(['before', 'after'], $data['slug'] ?? null);
    }

    /**
     * The old slug is read from the permalink, not from the virtual attribute, so a rename records the correct
     * before/after even when the slug was never read (and thus never materialised) before being written.
     */
    public function testRenameRecordsTheOldSlugWithoutReadingItFirst(): void
    {
        $created = $this->createEntry('before');

        $entry = TestEntry::findOne($created->id);
        $entry->slug = 'after';
        self::assertNotFalse($entry->update());

        self::assertSame(['before', 'after'], $this->findTrailData($entry, Trail::TYPE_UPDATE)['slug'] ?? null);
    }

    public function testTrailIsWrittenOnTheEntryNotThePermalink(): void
    {
        $entry = $this->createEntry('before');

        $entry->slug = 'after';
        self::assertNotFalse($entry->update());

        self::assertSame(0, (int)Trail::find()
            ->where(['model_class' => Permalink::class])
            ->count());
    }

    public function testCreateRecordsTheSlugInTheTrail(): void
    {
        $entry = $this->createEntry('brand-new');

        $data = $this->findTrailData($entry, Trail::TYPE_CREATE);

        self::assertNotEmpty($data);
        self::assertSame('brand-new', $data['slug'] ?? null);
    }

    /**
     * A parent rename rewrites descendant permalinks, but their own slug is unchanged, so they must not report one.
     */
    public function testRenamingAParentDoesNotTrailDescendantSlugs(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry('child', $parent);

        $parent->refresh();
        $parent->slug = 'renamed';
        self::assertNotFalse($parent->update());

        self::assertArrayNotHasKey('slug', $this->findTrailData($child, Trail::TYPE_UPDATE));
    }

    /**
     * @return array<string, mixed> the most recent trail data of that type, empty when no record was written
     */
    protected function findTrailData(TestEntry $entry, int $type): array
    {
        $trail = Trail::find()
            ->where([
                'model_class' => $entry->getTrailBehavior()->modelClass,
                'model_id' => $entry->id,
                'type' => $type,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();

        return $trail instanceof Trail ? (array)$trail->data : [];
    }

    protected function findRedirectTarget(string $requestUri): ?string
    {
        $redirect = Redirect::find()
            ->where(['request_uri' => $requestUri])
            ->limit(1)
            ->one();

        return $redirect?->url;
    }

    protected function findPermalinkUri(TestEntry $entry): ?string
    {
        $permalink = Permalink::find()
            ->whereModel(Entry::class, $entry->id)
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

    /**
     * {@see TestEntry::hasRoute()} needs sections or children, so an entry without either has no URL and therefore
     * nothing to redirect.
     */
    protected function createEntryWithSection(string $slug, ?TestEntry $parent = null): TestEntry
    {
        $entry = $this->createEntry($slug, $parent);

        $section = TestSection::create();
        $section->entry_id = $entry->id;
        $section->name = 'Test section';

        self::assertTrue($section->save(), implode(' ', $section->getErrorSummary(true)));
        $entry->refresh();

        return $entry;
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
