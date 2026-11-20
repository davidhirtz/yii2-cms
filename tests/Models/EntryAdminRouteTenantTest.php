<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Web\UrlManager;
use Yii;

/**
 * The German tenant of the fixture lives under the path `/de`.
 */
class EntryAdminRouteTenantTest extends TestCase
{
    use CmsFixtureTrait;

    public function testARecordOfAnotherTenantOpensInIt(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->entry->updateAttributes(['tenant_id' => 2]);

        $this->setCurrentTenant(1);

        self::assertSame("/de/admin/cms/entry/update?id={$section->entry->id}", Url::to($section->entry->getAdminRoute() ?: self::fail('No route.')));
        self::assertSame("/de/admin/cms/section/update?id=$section->id", Url::to($section->getAdminRoute() ?: self::fail('No route.')));

        $this->setCurrentTenant(2);

        self::assertSame(['/admin/cms/entry/update', 'id' => $section->entry->id], $section->entry->getAdminRoute());
        self::assertSame(['/admin/cms/section/update', 'id' => $section->id], $section->getAdminRoute());
    }

    private function setCurrentTenant(int $id): void
    {
        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);

        $manager->setTenant(TenantCollection::getById($id) ?? self::fail('No tenant.'));
    }
}
