<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Controllers;

use Hirtz\Cms\Controllers\SiteController;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Override;
use Yii;

final class SiteControllerPageCacheTest extends TestCase
{
    use CmsFixtureTrait;
    use FunctionalTestTrait;

    public function testEveryVisitorOfACachedPageGetsACsrfTokenOfTheirOwn(): void
    {
        Yii::$app->getModule('cms')->controllerMap['site'] = PageCacheSiteController::class;

        $tokens = [];

        foreach ([1, 2] as $visitor) {
            self::$client = new Browser(['HTTPS' => 'on']);
            self::$crawler = self::$client->request('GET', '/test-1');

            self::assertResponseIsSuccessful();
            self::assertNotNull(self::$client->getCookieJar()->get('_csrf'), "Visitor $visitor has no CSRF cookie.");

            $tokens[] = self::$crawler->filter('meta[name="csrf-token"]')->attr('content');
        }

        self::assertNotSame($tokens[0], $tokens[1]);
    }
}

class PageCacheSiteController extends SiteController
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            [
                'class' => PageCache::class,
                'params' => ['slug'],
            ],
        ];
    }
}
