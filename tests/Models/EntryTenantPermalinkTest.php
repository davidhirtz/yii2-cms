<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
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

    protected function findPermalink(TestEntry $entry): ?Permalink
    {
        return Permalink::find()
            ->andWhere(['entry_id' => $entry->id])
            ->whereLanguage()
            ->one();
    }

    protected function createTenant(): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Second Tenant';
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = 'https://www.second-domain.localhost';

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
}
