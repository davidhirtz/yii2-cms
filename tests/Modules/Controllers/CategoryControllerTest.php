<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Types\CategoryType;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Toolbars\CategoryFilterDropdown;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Override;
use Yii;
use yii\base\Event;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CategoryControllerTest extends TestCase
{
    use CmsFixtureTrait;

    private const int TYPE_TOPIC = 2;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->inheritNestedCategories = true;
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Category::class);
        parent::tearDown();
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

    public function testTheIndexTitleLinksToTheCategoryIndex(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index');

        self::assertIsString($html);
        self::assertMatchesRegularExpression('#<h1><a href="[^"]*/admin/cms/category/index[^"]*">#', $html);
    }

    public function testTheCategoryPagesLeadToTheEntriesOnlyWithTheirPermission(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index');

        self::assertIsString($html);
        self::assertStringNotContainsString('/admin/cms/entry/index', $html);

        $html = Yii::$app->runAction('admin/cms/category/update', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringNotContainsString('/admin/cms/entry/index', $html);
    }

    public function testCategoriesStayNestableWhenEntriesDoNotInheritThem(): void
    {
        TestEntry::getModule()->inheritNestedCategories = false;
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/update', ['id' => 3]);

        self::assertIsString($html);
        self::assertStringContainsString('Category[parent_id]', $html);
        self::assertStringContainsString('category/index?parent=3', $html);
    }

    public function testFlatCategoriesOfferNoSubcategories(): void
    {
        TestEntry::getModule()->enableNestedCategories = false;
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/update', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringNotContainsString('category/index?parent=1', $html);
        self::assertStringNotContainsString('category/create?parent=1', $html);
    }

    public function testAGroupingParentStaysAnOptionAndIsNoEntryFilter(): void
    {
        Yii::$container->set(Category::class, [
            'types' => fn (): array => [
                CategoryType::make(Category::TYPE_DEFAULT)->name('Group')->allowDescendants(false)->allowEntries(false),
                CategoryType::make(self::TYPE_TOPIC)->name('Topic'),
            ],
        ]);
        DefinitionRegistry::reset();

        Category::updateAll(['type' => self::TYPE_TOPIC], ['id' => 3]);
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/update', ['id' => 3]);
        self::assertIsString($html);
        self::assertMatchesRegularExpression('/<option value="1" selected/', $html);

        $html = (string)CategoryFilterDropdown::make();
        self::assertStringNotContainsString('category=1"', $html);
        self::assertStringContainsString('category=3"', $html);
    }

    public function testIndexListsTheChildrenOfAParent(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index', ['parent' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('Child category 1', $html);
        self::assertStringNotContainsString('Root category 2', $html);
    }

    public function testTheChildrenOfAParentDoNotRepeatItWhetherOrNotAnEntryUsesThem(): void
    {
        $this->login();

        $category = Category::create();
        $category->loadDefaultValues();
        $category->name = 'Child category 2';
        $category->slug = 'child-category-2';
        $category->parent_id = 1;
        self::assertTrue($category->insert(), print_r($category->getErrors(), true));

        // Child category 1 holds an entry, Child category 2 none.
        $html = Yii::$app->runAction('admin/cms/category/index', ['parent' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('Child category 2', $html);
        $ancestors = '<div class="small"><a href="' . Html::encode(Url::to(['/admin/cms/category/update', 'id' => 1]));
        self::assertStringNotContainsString($ancestors, $html);

        // A search lists every level, so a row names its branch.
        $html = Yii::$app->runAction('admin/cms/category/index', ['q' => 'Child']);
        self::assertIsString($html);
        self::assertSame(2, substr_count($html, $ancestors));
    }

    public function testIndexSearchesAcrossEveryLevel(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/category/index', ['q' => 'Child']);

        self::assertIsString($html);
        self::assertStringContainsString('Child category 1', $html);
        self::assertStringNotContainsString('Root category 2', $html);
    }

    public function testTheAdminIsNotFoundWhileTheCategoriesAreDisabled(): void
    {
        TestEntry::getModule()->enableCategories = false;
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/category/create');
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
     * The create button carries the page's own type, so the action has to answer for it (monorepo issue #161).
     */
    public function testCreateKeepsTheTypeFromTheQuery(): void
    {
        // The container is how a project declares types without subclassing, and the base `Category` the
        // controller loads has only its default one.
        Yii::$container->set(Category::class, [
            'types' => fn (): array => [
                CategoryType::make(Category::TYPE_DEFAULT)->name('Default'),
                CategoryType::make(self::TYPE_TOPIC)->name('Topic'),
            ],
        ]);

        $this->login();

        $this->post('admin/cms/category/create', [
            'Category' => [
                'status' => Category::STATUS_ENABLED,
                'name' => 'A typed category',
                'slug' => 'a-typed-category',
            ],
        ], ['type' => self::TYPE_TOPIC]);

        self::assertSame(self::TYPE_TOPIC, Category::findOne(['slug' => 'a-typed-category'])?->type);
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

    public function testARefusedDeleteFlashesTheReason(): void
    {
        $this->login();

        $handler = function (ModelEvent $event): void {
            self::assertInstanceOf(Model::class, $event->sender);
            $event->sender->addError('id', 'Still in use.');
            $event->isValid = false;
        };

        Event::on(Category::class, Category::EVENT_BEFORE_DELETE, $handler);

        try {
            $response = $this->post('admin/cms/category/delete', [], ['id' => 3]);
        } finally {
            Event::off(Category::class, Category::EVENT_BEFORE_DELETE, $handler);
        }

        self::assertInstanceOf(Response::class, $response);
        self::assertNotNull(Category::findOne(3));
        self::assertSame([['id' => 'Still in use.']], $this->getWebSession()->getFlash('danger'));
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/category/delete', ['id' => 3]);
    }

    public function testReorderRewritesThePositions(): void
    {
        $this->login();

        $html = $this->post('admin/cms/category/reorder', [
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
     * `CmsFixtureTrait` declares no accessor for the user fixture, since one would collide with `UserFixtureTrait`.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
