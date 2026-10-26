<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Controllers;

use Hirtz\Cms\Controllers\SiteController;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\Connection;
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

    /**
     * Bots ask for cached pages most: a hit, in an application of its own, must not cost a database connection.
     */
    public function testACachedPageIsAnsweredWithoutAConnection(): void
    {
        Yii::$app->getModule('cms')->controllerMap['site'] = PageCacheSiteController::class;

        $this->open('/test-1');
        $title = self::$crawler->filter('title')->text();

        $cache = Yii::$app->getCache();
        $db = Yii::$app->getDb();

        $this->reloadApplication();

        Yii::$app->set('cache', $cache);
        Yii::$app->getModule('cms')->controllerMap['site'] = PageCacheSiteController::class;

        $connection = new Connection([
            'dsn' => $db->dsn,
            'username' => $db->username,
            'password' => $db->password,
            'charset' => $db->charset,
            'enableSchemaCache' => true,
            'schemaCache' => $db->schemaCache,
        ]);

        Yii::$app->set('db', $connection);

        try {
            $this->open('/test-1');
        } finally {
            Yii::$app->set('db', $db);
        }

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($title);
        self::assertFalse($connection->getIsActive());
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
