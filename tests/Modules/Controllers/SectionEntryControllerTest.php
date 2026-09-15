<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
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

class SectionEntryControllerTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * The admin controllers resolve sections through the base `Section`, whose only known type is `TYPE_DEFAULT`,
     * so a write test has to use the one fixture section that carries it — the others are `TestSection` types and
     * `DynamicRangeValidator` refuses them on save.
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

    public function testCreateAddsTheEntryToTheSection(): void
    {
        $this->login();

        $response = $this->post('admin/cms/section-entry/create', ['section' => self::SECTION_ID, 'entry' => 1]);

        self::assertIsString($response);
        self::assertNotNull(SectionEntry::findOne(['section_id' => self::SECTION_ID, 'entry_id' => 1]));
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
        self::assertNull(SectionEntry::findOne(['section_id' => self::SECTION_ID, 'entry_id' => 1]));
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

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $first = $this->createSectionEntry(1);
        $second = $this->createSectionEntry(3);

        self::assertLessThan($second->position, $first->position);

        // the body carries the junction's own ids, not the entry ids
        $html = $this->post('admin/cms/section-entry/order', ['section' => self::SECTION_ID], [
            'section-entry' => [$second->id, $first->id],
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
        $sectionEntry->populateSectionRelation(Section::findOne(self::SECTION_ID));
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
