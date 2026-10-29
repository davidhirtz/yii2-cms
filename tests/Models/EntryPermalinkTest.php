<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;

/**
 * A permalink must always agree with {@see TestEntry::getFormattedSlug()} while the slug columns are the source of
 * truth.
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

    public function testPermalinkPointsAtItsEntry(): void
    {
        $entry = $this->createEntry('test-entry');
        $permalink = $entry->getPermalink();

        self::assertSame($entry->id, $permalink->entry_id);
        self::assertSame($entry->tenant_id, $permalink->tenant_id);
    }

    /**
     * Renaming touches no entry column, so Yii reports no affected rows, yet callers read the return value as success.
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
     * The cascade in {@see TestEntry::afterSave()} re-saves every descendant, so their permalinks follow a parent
     * rename.
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

    public function testARenameThatWouldMakeADescendantsUriTooLongIsRefused(): void
    {
        $parent = $this->createEntry('parent');
        $child = $this->createEntry(str_repeat('c', 100), $parent);
        $this->createEntry(str_repeat('g', 100), $child);

        $parent->refresh();
        $parent->slug = str_repeat('p', 100);

        self::assertFalse($parent->validate());
        self::assertTrue($parent->hasErrors('slug'));
        self::assertSame('parent/' . str_repeat('c', 100), $this->findPermalinkUri($child));
    }

    public function testPermalinkIsDeletedWithTheEntry(): void
    {
        $entry = $this->createEntry('test-entry');
        $id = $entry->id;

        self::assertNotFalse($entry->delete());

        self::assertSame(0, (int)Permalink::find()
            ->andWhere(['entry_id' => $id])
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
            ->andWhere(['entry_id' => $child->id])
            ->count());
    }

    /**
     * The unique index must be on the full path, since two entries may share a leaf under different parents.
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
     * A bare path would fire on every host and collide with another tenant's entry of the same slug.
     */
    public function testRedirectRequestUriIsQualifiedByTheTenantHost(): void
    {
        $entry = $this->createEntryWithSection('test-entry');

        $entry->slug = 'renamed';
        self::assertNotFalse($entry->update());

        self::assertTrue(Redirect::find()->where(['request_uri' => 'www.domain.localhost/test-entry'])->exists());
        self::assertFalse(Redirect::find()->where(['request_uri' => 'test-entry'])->exists());
    }

    /**
     * Recorded by the permalink action, since `RedirectBehavior` only saw them because every descendant happened to be
     * re-saved.
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

    /**
     * Left in place, the first rename's redirect flattened into `first` → `first` through
     * {@see Redirect::validateUrl()}, which the 404 handler followed forever.
     */
    public function testRenamingAnEntryBackDoesNotLeaveARedirectLoop(): void
    {
        $entry = $this->createEntryWithSection('first');

        $entry->slug = 'second';
        self::assertNotFalse($entry->update());

        $entry->refresh();
        $entry->slug = 'first';
        self::assertNotFalse($entry->update());

        self::assertNull($this->findRedirectTarget('first'));
        self::assertSame('first', $this->findRedirectTarget('second'));
        self::assertSame(1, Redirect::find()->count());
    }

    public function testRenamingAParentBackDoesNotLeaveARedirectLoopForDescendants(): void
    {
        $parent = $this->createEntryWithSection('parent');
        $this->createEntryWithSection('child', $parent);

        $parent->refresh();
        $parent->slug = 'renamed';
        self::assertNotFalse($parent->update());

        $parent->refresh();
        $parent->slug = 'parent';
        self::assertNotFalse($parent->update());

        self::assertNull($this->findRedirectTarget('parent'));
        self::assertNull($this->findRedirectTarget('parent/child'));

        self::assertSame('parent', $this->findRedirectTarget('renamed'));
        self::assertSame('parent/child', $this->findRedirectTarget('renamed/child'));
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
     * The slug is no longer an entry column, so nothing reports it as changed unless the model does.
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
     * The old slug is read from the permalink, since the virtual attribute may never have been materialised before
     * being written.
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
     * @return array<string, mixed>
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

    /**
     * The request URI is a path on the default tenant, whose host the stored URI carries.
     */
    protected function findRedirectTarget(string $requestUri): ?string
    {
        $redirect = Redirect::find()
            ->where(['request_uri' => "www.domain.localhost/$requestUri"])
            ->limit(1)
            ->one();

        return $redirect?->url;
    }

    protected function findPermalinkUri(TestEntry $entry): ?string
    {
        $permalink = Permalink::find()
            ->andWhere(['entry_id' => $entry->id])
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
     * {@see TestEntry::hasRoute()} needs sections or children, so an entry without either has nothing to redirect.
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

    /**
     * The URL manager resolves the default tenant as a request on its host would, so a redirect target on it is
     * relative.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableNestedEntries = true;

        $manager = Yii::$app->getUrlManager();

        if ($manager instanceof UrlManager && ($tenant = TenantCollection::getDefault())) {
            $manager->setTenant($tenant);
        }
    }
}
