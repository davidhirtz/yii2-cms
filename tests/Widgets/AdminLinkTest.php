<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Widgets\AdminLink;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;

/**
 * The widget itself is covered in the skeleton; what a cms record has to answer for is that the frontend overlay
 * reaches its admin page at all. Before {@see AdminModelInterface::getPermissionName()} the widget asked
 * `method_exists()` and rendered nothing otherwise, which was every model here but the asset.
 */
class AdminLinkTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * @param callable(self): AdminModelInterface $record
     */
    #[DataProvider('recordDataProvider')]
    public function testTheRecordsAdminRouteIsLinked(callable $record): void
    {
        $model = $record($this);
        $this->login($model->getPermissionName());

        $route = $model->getAdminRoute();
        self::assertNotFalse($route);

        self::assertStringContainsString(
            Yii::$app->getUrlManager()->createUrl($route),
            AdminLink::tag($model),
        );
    }

    /**
     * @return array<string, array{callable(self): AdminModelInterface}>
     */
    public static function recordDataProvider(): array
    {
        return [
            'entry' => [static fn (self $t): AdminModelInterface => $t->getEntryFromFixture('page-enabled')],
            'section' => [static fn (self $t): AdminModelInterface => $t->getSectionFromFixture('section-headline')],
            'category' => [static fn (self $t): AdminModelInterface => $t->getCategoryFromFixture('root-1')],
            'asset' => [static fn (self $t): AdminModelInterface => $t->getAssetFromFixture('entry-asset')],
        ];
    }

    /**
     * `CmsFixtureTrait` declares the user fixture but no accessor for it, so the lookup is repeated here.
     */
    private function login(string $permission): void
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');
        $user = User::findOne($fixture->data['admin']['id']);

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission($permission), $user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
