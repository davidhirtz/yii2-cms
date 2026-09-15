<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Category;
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

class CategoryControllerTest extends TestCase
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

    public function testIndexListsTheRootCategories(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index');

        self::assertIsString($html);
        self::assertStringContainsString('Root category 1', $html);
        self::assertStringContainsString('Root category 2', $html);

        // a nested category belongs to its parent's listing, not to the root one
        self::assertStringNotContainsString('Child category 1', $html);
    }

    public function testIndexListsTheChildrenOfAParent(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index', ['parent' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('Child category 1', $html);
        self::assertStringNotContainsString('Root category 2', $html);
    }

    public function testIndexSearchesAcrossEveryLevel(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index', ['q' => 'Child']);

        self::assertIsString($html);
        self::assertStringContainsString('Child category 1', $html);
        self::assertStringNotContainsString('Root category 2', $html);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/category/index');
    }

    public function testCreateRendersTheForm(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/create');

        self::assertIsString($html);
        self::assertStringContainsString('name="Category[name]"', $html);
        self::assertStringContainsString('name="Category[slug]"', $html);
    }

    public function testCreateInsertsTheCategory(): void
    {
        $this->login();

        $response = $this->post('admin/cms/category/create', [
            'Category' => [
                'status' => Category::STATUS_ENABLED,
                'name' => 'A new category',
                'slug' => 'a-new-category',
            ],
        ]);

        self::assertInstanceOf(Response::class, $response);

        $category = Category::findOne(['slug' => 'a-new-category']);

        self::assertNotNull($category);
        self::assertNull($category->parent_id);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testCreateKeepsTheParentFromTheQuery(): void
    {
        $this->login();

        $this->post('admin/cms/category/create', [
            'Category' => [
                'status' => Category::STATUS_ENABLED,
                'name' => 'A nested category',
                'slug' => 'a-nested-category',
            ],
        ], ['parent' => 1]);

        self::assertSame(1, Category::findOne(['slug' => 'a-nested-category'])?->parent_id);
    }

    /**
     * The form posts to the same action as the save, so only `Request::isFormReload()` separates a type change
     * from a submit.
     */
    public function testAFormReloadDoesNotSave(): void
    {
        $this->login();

        $html = $this->post('admin/cms/category/create', [
            'Category' => [
                'status' => Category::STATUS_ENABLED,
                'name' => 'Not saved',
                'slug' => 'not-saved',
            ],
        ], reload: true);

        self::assertIsString($html);
        self::assertNull(Category::findOne(['slug' => 'not-saved']));
    }

    public function testCreateRendersTheErrorsOfAnInvalidCategory(): void
    {
        $this->login();
        $count = Category::find()->count();

        $html = $this->post('admin/cms/category/create', [
            'Category' => ['name' => ''],
        ]);

        self::assertIsString($html);
        self::assertSame($count, Category::find()->count());
    }

    public function testUpdateRendersTheCategory(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/update', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('value="Root category 1"', $html);
    }

    public function testUpdateSavesTheCategory(): void
    {
        $this->login();

        $response = $this->post('admin/cms/category/update', [
            'Category' => [
                'status' => Category::STATUS_ENABLED,
                'name' => 'Renamed',
                'slug' => 'root-1',
            ],
        ], ['id' => 1]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('Renamed', Category::findOne(1)->name);
    }

    public function testUpdateOfAnUnknownCategoryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/category/update', ['id' => 99999]);
    }

    public function testDeleteRemovesTheCategoryAndItsJunctions(): void
    {
        $this->login();

        $response = $this->post('admin/cms/category/delete', [], ['id' => 3]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(Category::findOne(3));
        self::assertSame(0, (int)EntryCategory::find()->where(['category_id' => 3])->count());
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/category/delete', ['id' => 3]);
    }

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $html = $this->post('admin/cms/category/order', [
            'category' => [2, 1],
        ]);

        self::assertIsString($html);
        self::assertGreaterThan(Category::findOne(2)->lft, Category::findOne(1)->lft);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $bodyParams, array $params = [], bool $reload = false): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        if ($reload) {
            $request->getHeaders()->set('X-Form-Reload', 'true');
        }

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $permission = Yii::$app->getAuthManager()->getPermission(Category::AUTH_CATEGORY);
        Yii::$app->getAuthManager()->assign($permission, $user->id);

        $this->getWebUser()->setIdentity($user);

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
