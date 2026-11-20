<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Search;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchHit;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchResultBuilder;
use Hirtz\Skeleton\Search\SearchResultSet;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;

class TenantSearchResultTest extends TestCase
{
    use CmsFixtureTrait;

    public function testAResultOfTheAdminsTenantIsNotNamed(): void
    {
        $result = $this->buildResult(2);

        self::assertNull($result->badge);
        self::assertSame('/de/admin/cms/entry/update?id=' . $this->getEntry()->id, Url::to($result->route ?: self::fail('No route.')));
    }

    public function testAResultOfAnotherTenantIsNamedAndOpensThere(): void
    {
        $result = $this->buildResult(1);

        self::assertSame('German Tenant', $result->badge);
        self::assertSame('/de/admin/cms/entry/update?id=' . $this->getEntry()->id, Url::to($result->route ?: self::fail('No route.')));
    }

    private function buildResult(int $tenantId): SearchResult
    {
        $this->getWebUser()->setIdentity(User::findOne(['name' => 'owner']));

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->setTenant(TenantCollection::getById($tenantId) ?? self::fail('No tenant.'));

        $search = new TenantSearchResultTestSearch([new SearchHit(Entry::class, $this->getEntry()->id, 1)]);

        /** @var SearchResultBuilder $builder */
        $builder = Yii::createObject(SearchResultBuilder::class, [$search]);

        return $builder->build(new SearchRequest(query: 'x'), false)[0] ?? self::fail('No result.');
    }

    /**
     * The German tenant of the fixture lives under the path `/de`.
     */
    private function getEntry(): Entry
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $entry->updateAttributes(['tenant_id' => 2]);

        return $entry;
    }
}

class TenantSearchResultTestSearch extends Search
{
    /**
     * @param list<SearchHit> $hits
     */
    public function __construct(private readonly array $hits)
    {
        parent::__construct();
    }

    #[Override]
    public function search(SearchRequest $request): SearchResultSet
    {
        return new SearchResultSet($this->hits);
    }
}
