<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SectionEntryControllerTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * The base `Section` the controllers resolve knows only `TYPE_DEFAULT`, so a write test uses the one fixture
     * section carrying it.
     */
    private const int SECTION_ID = 6;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableSectionEntries = true;
    }

    public function testIndexListsTheEntriesOfTheSection(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/section-entry/index', ['section' => 3]);

        self::assertIsString($html);
        self::assertStringContainsString('Test Page – Enabled', $html);
    }

    public function testTheRecordsOfAnotherTenantAreListedFromTheDefaultTenantsHost(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;
        Entry::updateAll(['tenant_id' => 2]);

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->setTenant(Tenant::findOne(1) ?? self::fail('The seeded tenant is missing.'));

        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry/index', ['parent' => 1]);
        self::assertIsString($html);
        self::assertStringContainsString('id="entry-4"', $html);

        $html = Yii::$app->runAction('admin/cms/section-entry/index', ['section' => 3]);
        self::assertIsString($html);
        self::assertStringContainsString('Test Child 1', $html);

        $html = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => 3]);
        self::assertIsString($html);
        self::assertStringContainsString('Test Page – Draft', $html);
    }

    public function testIndexOfAnUnknownSectionIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/section-entry/index', ['section' => 99999]);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/section-entry/index', ['section' => 3]);
    }

    public function testCreateRendersThePickerOfEntriesToAdd(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => 3]);

        self::assertIsString($html);
        self::assertStringContainsString('Test Page – Draft', $html);
    }

    /**
     * A configured default type is the one the picker opens on, so the action redirects rather than rendering the
     * untyped listing first.
     */
    public function testCreateRedirectsToTheDefaultEntryType(): void
    {
        $this->login();
        TestEntry::getModule()->defaultEntryType = TestEntry::TYPE_PAGE;

        $response = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => self::SECTION_ID]);

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('type=' . TestEntry::TYPE_PAGE, (string)$response->getHeaders()->get('location'));
    }

    public function testCreateListsEveryTypeWhenTheSectionRefusesTheDefaultOne(): void
    {
        Yii::$container->setDefinitions([
            Entry::class => TestEntry::class,
            Section::class => [
                'types' => fn (): array => [
                    SectionType::make(Section::TYPE_DEFAULT)
                        ->name('Text')
                        ->entriesTypes(TestEntry::TYPE_POST),
                ],
            ],
        ]);

        Entry::instance(true);
        DefinitionRegistry::reset();

        try {
            $this->login();
            TestEntry::getModule()->defaultEntryType = TestEntry::TYPE_PAGE;

            $html = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => self::SECTION_ID]);

            self::assertIsString($html);
        } finally {
            Yii::$container->clear(Entry::class);
            Yii::$container->clear(Section::class);
            Entry::instance(true);
            DefinitionRegistry::reset();
        }
    }

    public function testCreateAddsTheEntryToTheSection(): void
    {
        $this->login();

        $params = ['section' => self::SECTION_ID, 'entry' => 1, 'type' => 1, 'q' => 'Müller'];
        $this->getWebRequest()->setQueryParams($params);
        $response = $this->post('admin/cms/section-entry/create', $params);

        // back to the picker as it was filtered, without the entry just added
        self::assertInstanceOf(Response::class, $response);
        $location = (string)$response->getHeaders()->get('location');
        self::assertStringContainsString('type=1', $location);
        self::assertStringContainsString('q=M%C3%BCller', $location);
        self::assertStringNotContainsString('entry=1', $location);

        self::assertNotNull(SectionEntry::findOne(['model_id' => self::SECTION_ID, 'entry_id' => 1]));
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));

        // the section keeps its own count of them
        self::assertSame(1, Section::findOne(self::SECTION_ID)->entry_count);
    }

    public function testCreateReportsAnEntryThatIsAlreadyThere(): void
    {
        $this->login();
        $this->createSectionEntry(1);

        $this->post('admin/cms/section-entry/create', ['section' => self::SECTION_ID, 'entry' => 1]);

        self::assertEmpty($this->getWebSession()->getFlash('success'));
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
    }

    public function testCreateOfAnUnknownEntryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/cms/section-entry/create', ['section' => self::SECTION_ID, 'entry' => 99999]);
    }

    public function testDeleteRemovesTheEntryFromTheSection(): void
    {
        $this->login();
        $this->createSectionEntry(1);

        $response = $this->post('admin/cms/section-entry/delete', ['section' => self::SECTION_ID, 'entry' => 1]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(SectionEntry::findOne(['model_id' => self::SECTION_ID, 'entry_id' => 1]));
        self::assertSame(0, Section::findOne(self::SECTION_ID)->entry_count);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    /**
     * A second click on an entry that is already gone is a 404, not a fatal.
     */
    public function testDeleteOfAnEntryThatIsNotThereIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/cms/section-entry/delete', ['section' => self::SECTION_ID, 'entry' => 1]);
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();
        $this->createSectionEntry(1);

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/section-entry/delete', ['section' => self::SECTION_ID, 'entry' => 1]);
    }

    public function testOnlyTheLinksOwnOrderIsReorderable(): void
    {
        $this->login();
        Section::updateAll(['type' => Section::TYPE_DEFAULT], ['id' => 3]);

        foreach ([[null, true], [['position' => SORT_ASC], false]] as [$orderBy, $isReorderable]) {
            Yii::$container->set(Section::class, [
                'types' => fn (): array => [
                    SectionType::make(Section::TYPE_DEFAULT)->name('Links')->entriesOrderBy($orderBy),
                ],
            ]);

            DefinitionRegistry::reset();

            $html = Yii::$app->runAction('admin/cms/section-entry/index', ['section' => 3]);

            self::assertIsString($html);
            self::assertSame($isReorderable, str_contains($html, 'data-reorder-url'), print_r($orderBy, true));
        }
    }

    public function testReorderRewritesThePositions(): void
    {
        $this->login();

        $first = $this->createSectionEntry(1);
        $second = $this->createSectionEntry(3);

        self::assertLessThan($second->position, $first->position);

        // the body carries the junction's own ids, not the entry ids
        $html = $this->post('admin/cms/section-entry/reorder', ['section' => self::SECTION_ID], [
            'entry-relation' => [$second->id, $first->id],
        ]);

        self::assertIsString($html);

        $first = SectionEntry::findOne($first->id);
        $second = SectionEntry::findOne($second->id);

        self::assertLessThan($first->position, $second->position);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    private function createSectionEntry(int $entryId): SectionEntry
    {
        $sectionEntry = SectionEntry::create();
        $sectionEntry->populateModelRelation(Section::findOne(self::SECTION_ID));
        $sectionEntry->populateEntryRelation(TestEntry::findOne($entryId));

        self::assertTrue($sectionEntry->insert(), print_r($sectionEntry->getErrors(), true));

        return $sectionEntry;
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $permission = Yii::$app->getAuthManager()->getPermission(TestEntry::AUTH_ENTRY);
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
