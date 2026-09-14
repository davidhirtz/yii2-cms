<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntryCategoryControllerTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->inheritNestedCategories = true;
    }

    public function testIndexMarksTheCategoriesTheEntryIsIn(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('Root category 1', $html);

        // the linked ones offer the remove route, the others the link route
        self::assertStringContainsString('entry-category/delete', $html);
    }

    public function testIndexOfAnUnknownEntryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 99999]);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1]);
    }

    public function testCreateLinksTheEntryToTheCategory(): void
    {
        $this->login();

        $response = $this->post('admin/cms/entry-category/create', ['entry' => 2, 'category' => 2]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNotNull(EntryCategory::findOne(['entry_id' => 2, 'category_id' => 2]));

        self::assertSame(
            ['Category linked to entry.'],
            Yii::$app->getSession()->getFlash('success')
        );
    }

    /**
     * Linking a nested category links its ancestors too, which the flash has to say.
     */
    public function testCreateCountsTheInheritedAncestors(): void
    {
        $this->login();

        $this->post('admin/cms/entry-category/create', ['entry' => 3, 'category' => 3]);

        self::assertNotNull(EntryCategory::findOne(['entry_id' => 3, 'category_id' => 3]));
        self::assertNotNull(EntryCategory::findOne(['entry_id' => 3, 'category_id' => 1]));

        self::assertSame(
            ['2 categories linked to entry.'],
            Yii::$app->getSession()->getFlash('success')
        );
    }

    public function testCreateReportsALinkThatIsAlreadyThere(): void
    {
        $this->login();

        $this->post('admin/cms/entry-category/create', ['entry' => 1, 'category' => 1]);

        self::assertEmpty(Yii::$app->getSession()->getFlash('success'));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('danger'));
    }

    public function testCreateReportsAnUnknownCategory(): void
    {
        $this->login();

        $this->post('admin/cms/entry-category/create', ['entry' => 1, 'category' => 99999]);

        self::assertEmpty(Yii::$app->getSession()->getFlash('success'));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('danger'));
    }

    public function testDeleteRemovesTheLink(): void
    {
        $this->login();

        $response = $this->post('admin/cms/entry-category/delete', ['entry' => 2, 'category' => 1]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(EntryCategory::findOne(['entry_id' => 2, 'category_id' => 1]));

        self::assertSame(
            ['Category removed from entry.'],
            Yii::$app->getSession()->getFlash('success')
        );
    }

    /**
     * Removing a category removes the branch below it, which the flash has to say.
     */
    public function testDeleteCountsTheInheritedDescendants(): void
    {
        $this->login();

        $this->post('admin/cms/entry-category/delete', ['entry' => 1, 'category' => 1]);

        self::assertNull(EntryCategory::findOne(['entry_id' => 1, 'category_id' => 1]));
        self::assertNull(EntryCategory::findOne(['entry_id' => 1, 'category_id' => 3]));

        self::assertSame(
            ['2 categories removed from entry.'],
            Yii::$app->getSession()->getFlash('success')
        );
    }

    /**
     * A second click on a link that is already gone is a 404, not a fatal.
     */
    public function testDeleteOfALinkThatIsNotThereIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/cms/entry-category/delete', ['entry' => 2, 'category' => 2]);
    }

    public function testCreateAndDeleteRefuseAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/entry-category/create', ['entry' => 2, 'category' => 2]);
    }

    public function testOrderRewritesThePositionsWithinTheCategory(): void
    {
        $this->login();

        $html = $this->post('admin/cms/entry-category/order', ['category' => 1], ['entry' => [2, 1]]);

        self::assertIsString($html);

        $first = EntryCategory::findOne(['entry_id' => 2, 'category_id' => 1]);
        $second = EntryCategory::findOne(['entry_id' => 1, 'category_id' => 1]);

        self::assertLessThan($second->position, $first->position);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    private function post(string $route, array $params = [], array $bodyParams = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $permission = Yii::$app->getAuthManager()->getPermission(TestEntry::AUTH_ENTRY);
        Yii::$app->getAuthManager()->assign($permission, $user->id);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }

    /**
     * `CmsFixtureTrait` declares the user fixture but no accessor for it: adding one would collide with
     * `UserFixtureTrait` for the tests that use both.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
