<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;

/**
 * Two tenants may serve the same slug, so a permalink is only unique per tenant.
 */
class EntryTenantPermalinkTest extends TestCase
{
    public function testPermalinkIsScopedToItsTenant(): void
    {
        $first = TenantCollection::getDefault();
        $second = $this->createTenant();

        $one = $this->createEntry('home', $first);
        $two = $this->createEntry('home', $second);

        self::assertNotNull($this->findPermalink($one), 'The first tenant has no permalink.');
        self::assertNotNull($this->findPermalink($two), 'The second tenant lost its permalink to the first.');

        self::assertSame($first->id, $this->findPermalink($one)->tenant_id);
        self::assertSame($second->id, $this->findPermalink($two)->tenant_id);
    }

    public function testPermalinkIsFoundForTheCurrentTenantOnly(): void
    {
        $first = TenantCollection::getDefault();
        $second = $this->createTenant();

        $this->createEntry('home', $first);
        $this->createEntry('home', $second);

        $permalinks = Permalink::find()
            ->whereUri('home')
            ->andWhere(['tenant_id' => $second->id])
            ->all();

        self::assertCount(1, $permalinks);
        self::assertSame($second->id, $permalinks[0]->tenant_id);
    }

    /**
     * The current tenant is the default one, as in the admin, so a target on another host must be absolute.
     */
    public function testRenameOnAnotherHostRecordsARedirectOnThatHost(): void
    {
        $tenant = $this->createTenant('https://www.second-domain.localhost');
        $entry = $this->createEntryWithSection('old', $tenant);

        $entry->slug = 'new';
        self::assertNotFalse($entry->update());

        $redirect = $this->findRedirect('www.second-domain.localhost/old');

        self::assertNotNull($redirect);
        self::assertSame('https://www.second-domain.localhost/new', $redirect->url);
    }

    public function testRenameOnAPathTenantKeepsThePathPrefix(): void
    {
        $tenant = $this->createTenant('https://www.domain.localhost/second');
        $entry = $this->createEntryWithSection('old', $tenant);

        $entry->slug = 'new';
        self::assertNotFalse($entry->update());

        $redirect = $this->findRedirect('www.domain.localhost/second/old');

        self::assertNotNull($redirect);
        self::assertSame('second/new', $redirect->url);
    }

    public function testRenamingTheSameSlugOnTwoTenantsRecordsTwoRedirects(): void
    {
        $first = TenantCollection::getDefault();
        $second = $this->createTenant('https://www.second-domain.localhost');

        $one = $this->createEntryWithSection('shared', $first);
        $two = $this->createEntryWithSection('shared', $second);

        foreach ([$one, $two] as $entry) {
            $entry->slug = 'moved';
            self::assertNotFalse($entry->update());
        }

        self::assertSame('moved', $this->findRedirect('www.domain.localhost/shared')?->url);
        self::assertSame('https://www.second-domain.localhost/moved', $this->findRedirect('www.second-domain.localhost/shared')?->url);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $manager = Yii::$app->getUrlManager();

        if ($manager instanceof UrlManager && ($tenant = TenantCollection::getDefault())) {
            $manager->setTenant($tenant);
        }
    }

    protected function findRedirect(string $requestUri): ?Redirect
    {
        return Redirect::find()
            ->where(['request_uri' => $requestUri])
            ->one();
    }

    protected function findPermalink(TestEntry $entry): ?Permalink
    {
        return Permalink::find()
            ->andWhere(['entry_id' => $entry->id])
            ->whereLanguage()
            ->one();
    }

    protected function createTenant(string $url = 'https://www.second-domain.localhost'): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Second Tenant';
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = $url;

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));

        return $tenant;
    }

    protected function createEntry(string $slug, Tenant $tenant): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = ucfirst($slug);
        $entry->slug = $slug;
        $entry->populateTenantRelation($tenant);

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }

    /**
     * A redirect is only recorded for an entry with a route, which needs a section.
     */
    protected function createEntryWithSection(string $slug, Tenant $tenant): TestEntry
    {
        $entry = $this->createEntry($slug, $tenant);

        $section = TestSection::create();
        $section->entry_id = $entry->id;
        $section->name = 'Test section';

        self::assertTrue($section->save(), implode(' ', $section->getErrorSummary(true)));
        $entry->refresh();

        return $entry;
    }
}
