<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The records are built here rather than taken from the cms fixture, whose sections carry `TestSection` types that
 * the base `Section` the controller loads refuses to save.
 */
class SectionControllerTest extends TestCase
{
    use UserFixtureTrait;

    private const int TYPE_GALLERY = 2;

    private Entry $entry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entry = $this->createEntry('Page', 'page');
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        parent::tearDown();
    }

    public function testIndexListsTheSectionsOfTheEntry(): void
    {
        $this->login();

        $this->createSection('First');
        $this->createSection('Second');

        $html = Yii::$app->runAction('admin/cms/section/index', ['entry' => $this->entry->id]);

        self::assertIsString($html);
        self::assertStringContainsString('First', $html);
        self::assertStringContainsString('Second', $html);
    }

    public function testIndexOfAnUnknownEntryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/section/index', ['entry' => 99999]);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/section/index', ['entry' => $this->entry->id]);
    }

    public function testCreateRendersTheForm(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/section/create', ['entry' => $this->entry->id]);

        self::assertIsString($html);
        self::assertStringContainsString('name="Section[name]"', $html);
        self::assertSame(0, (int)Section::find()->where(['entry_id' => $this->entry->id])->count());
    }

    public function testCreateInsertsThePostedSectionAndOpensIt(): void
    {
        $this->login();

        $response = $this->post('admin/cms/section/create', ['entry' => $this->entry->id], [
            'Section' => ['name' => 'Intro'],
        ]);

        self::assertInstanceOf(Response::class, $response);

        $section = Section::findOne(['entry_id' => $this->entry->id]);

        self::assertNotNull($section);
        self::assertSame('Intro', $section->name);
        self::assertStringContainsString("id=$section->id", (string)$response->getHeaders()->get('location'));
        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);
    }

    public function testCreateDoesNotInsertOnAFormReload(): void
    {
        $this->login();

        $html = $this->post('admin/cms/section/create', ['entry' => $this->entry->id], [
            'Section' => ['name' => 'Intro'],
        ], reload: true);

        self::assertIsString($html);
        self::assertSame(0, (int)Section::find()->where(['entry_id' => $this->entry->id])->count());
    }

    /**
     * The create button carries the grid's own type filter, so the action has to answer for it (monorepo issue
     * #161).
     */
    public function testCreateTakesTheTypeFromTheQuery(): void
    {
        // The container is how a project declares types without subclassing, and the base `Section` the
        // controller loads has only its default one.
        Yii::$container->set(Section::class, [
            'types' => fn (): array => [
                SectionType::make(Section::TYPE_DEFAULT)->name('Default'),
                SectionType::make(self::TYPE_GALLERY)->name('Gallery'),
            ],
        ]);

        $this->login();

        $this->post('admin/cms/section/create', [
            'entry' => $this->entry->id,
            'type' => self::TYPE_GALLERY,
        ], ['Section' => ['name' => 'Gallery']]);

        $section = Section::findOne(['entry_id' => $this->entry->id]);

        self::assertNotNull($section);
        self::assertSame(self::TYPE_GALLERY, $section->type);
    }

    public function testCreateSetInsertsTheDeclaredSections(): void
    {
        $this->login();
        $this->setSectionSets();

        $response = $this->post('admin/cms/section/create-set', ['entry' => $this->entry->id], ['set' => '1']);

        self::assertInstanceOf(Response::class, $response);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));

        $sections = Section::find()
            ->where(['entry_id' => $this->entry->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        self::assertSame(['Hero', 'Text'], array_map(fn (Section $section): string => (string)$section->name, $sections));
        self::assertSame(2, Entry::findOne($this->entry->id)->section_count);
    }

    public function testCreateSetWithAnUnknownSetIsNotFound(): void
    {
        $this->login();
        $this->setSectionSets();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/cms/section/create-set', ['entry' => $this->entry->id], ['set' => '99']);
    }

    /**
     * A set the entry is not offered must be refused by the action, not only left out of the modal's select.
     */
    public function testCreateSetRefusesASetTheEntryIsNotOffered(): void
    {
        $this->login();

        Section::getModule()->setSectionSets(fn (): array => [
            SectionSet::make(1)
                ->name('Landing page')
                ->available(false)
                ->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
        ]);

        try {
            $this->post('admin/cms/section/create-set', ['entry' => $this->entry->id], ['set' => '1']);
            self::fail('The action accepted a set the entry is not offered.');
        } catch (NotFoundHttpException) {
            self::assertSame(0, (int)Section::find()->where(['entry_id' => $this->entry->id])->count());
        }
    }

    public function testCreateSetRefusesAGetRequest(): void
    {
        $this->login();
        $this->setSectionSets();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/section/create-set', ['entry' => $this->entry->id]);
    }

    public function testUpdateSavesTheSection(): void
    {
        $this->login();
        $section = $this->createSection('Original');

        $response = $this->post('admin/cms/section/update', ['id' => $section->id], [
            'Section' => ['name' => 'Renamed'],
        ]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('Renamed', Section::findOne($section->id)->name);
    }

    public function testAFormReloadDoesNotSave(): void
    {
        $this->login();
        $section = $this->createSection('Original');

        $html = $this->post('admin/cms/section/update', ['id' => $section->id], [
            'Section' => ['name' => 'Renamed'],
        ], reload: true);

        self::assertIsString($html);
        self::assertSame('Original', Section::findOne($section->id)->name);
    }

    public function testUpdateOfAnUnknownSectionIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/section/update', ['id' => 99999]);
    }

    public function testUpdateAllChangesTheSelectedSections(): void
    {
        $this->login();

        $first = $this->createSection('First');
        $second = $this->createSection('Second');
        $third = $this->createSection('Third');

        $this->post('admin/cms/section/update-all', [], [
            'selection' => [(string)$first->id, (string)$second->id],
            'Section' => ['status' => Section::STATUS_DISABLED],
        ]);

        self::assertSame(Section::STATUS_DISABLED, Section::findOne($first->id)->status);
        self::assertSame(Section::STATUS_DISABLED, Section::findOne($second->id)->status);
        self::assertSame(Section::STATUS_ENABLED, Section::findOne($third->id)->status);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testMoveTakesTheSectionToAnotherEntryAndFixesBothCounts(): void
    {
        $this->login();

        $section = $this->createSection('Moving');
        $target = $this->createEntry('Target', 'target');

        $response = $this->post('admin/cms/section/move', ['id' => $section->id, 'entry' => $target->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame($target->id, Section::findOne($section->id)->entry_id);

        self::assertSame(0, Entry::findOne($this->entry->id)->section_count);
        self::assertSame(1, Entry::findOne($target->id)->section_count);
    }

    public function testDuplicateCopiesTheSectionIntoTheSameEntry(): void
    {
        $this->login();
        $section = $this->createSection('Original');

        $response = $this->post('admin/cms/section/duplicate', ['id' => $section->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(2, (int)Section::find()->where(['entry_id' => $this->entry->id])->count());
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testDuplicateCanTargetAnotherEntry(): void
    {
        $this->login();

        $section = $this->createSection('Original');
        $target = $this->createEntry('Target', 'target');

        $this->post('admin/cms/section/duplicate', ['id' => $section->id, 'entry' => $target->id]);

        self::assertSame(1, (int)Section::find()->where(['entry_id' => $target->id])->count());
    }

    public function testDeleteRemovesTheSectionAndFixesTheCount(): void
    {
        $this->login();
        $section = $this->createSection('Doomed');

        $response = $this->post('admin/cms/section/delete', ['id' => $section->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(Section::findOne($section->id));
        self::assertSame(0, Entry::findOne($this->entry->id)->section_count);
    }

    /**
     * The grid removes the row itself, so an ajax delete answers with nothing rather than a redirect.
     */
    public function testAnAjaxDeleteAnswersWithNothing(): void
    {
        $this->login();
        $section = $this->createSection('Doomed');

        // the headers are resolved once, so `$_SERVER` alone would come too late
        $this->getWebRequest()->getHeaders()->set('X-Requested-With', 'XMLHttpRequest');

        self::assertSame('', $this->post('admin/cms/section/delete', ['id' => $section->id]));
        self::assertNull(Section::findOne($section->id));
    }

    public function testDeleteAllRemovesTheSelectedSectionsAndFixesTheCountOnce(): void
    {
        $this->login();

        $first = $this->createSection('First');
        $second = $this->createSection('Second');
        $third = $this->createSection('Third');

        $response = $this->post('admin/cms/section/delete-all', [], [
            'selection' => [(string)$first->id, (string)$second->id],
        ]);

        self::assertInstanceOf(Response::class, $response);

        self::assertNull(Section::findOne($first->id));
        self::assertNull(Section::findOne($second->id));
        self::assertNotNull(Section::findOne($third->id));

        self::assertSame(1, Entry::findOne($this->entry->id)->section_count);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testDeleteAllWithoutASelectionDeletesNothing(): void
    {
        $this->login();
        $section = $this->createSection('Kept');

        $this->post('admin/cms/section/delete-all');

        self::assertNotNull(Section::findOne($section->id));
        self::assertEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testDeleteAllRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/section/delete-all');
    }

    /**
     * The row's own delete button is off by default, so the footer is the only way to delete from the grid — and the
     * footer is only offered for more than one section.
     */
    public function testTheGridOffersTheSelection(): void
    {
        $this->login();

        $this->createSection('First');
        $this->createSection('Second');

        $html = Yii::$app->runAction('admin/cms/section/index', ['entry' => $this->entry->id]);

        self::assertIsString($html);
        self::assertStringContainsString('name="selection[]"', $html);
        self::assertStringContainsString('/admin/cms/section/delete-all', $html);
    }

    public function testTheGridOffersNoSelectionForASingleSection(): void
    {
        $this->login();

        $this->createSection('Only');

        $html = Yii::$app->runAction('admin/cms/section/index', ['entry' => $this->entry->id]);

        self::assertIsString($html);
        self::assertStringNotContainsString('name="selection[]"', $html);
        self::assertStringNotContainsString('/admin/cms/section/delete-all', $html);
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();
        $section = $this->createSection('Doomed');

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/section/delete', ['id' => $section->id]);
    }

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $first = $this->createSection('First');
        $second = $this->createSection('Second');

        $html = $this->post('admin/cms/section/order', ['entry' => $this->entry->id], [
            'section' => [$second->id, $first->id],
        ]);

        self::assertIsString($html);
        self::assertLessThan(
            Section::findOne($first->id)->position,
            Section::findOne($second->id)->position
        );
    }

    public function testEntriesRendersThePickerForTheSection(): void
    {
        $this->login();
        $section = $this->createSection('Linking');

        $module = Section::getModule();
        $module->enableSectionEntries = true;

        try {
            $html = Yii::$app->runAction('admin/cms/section/entries', ['id' => $section->id]);

            self::assertIsString($html);
            self::assertStringContainsString('Page', $html);
        } finally {
            $module->enableSectionEntries = false;
        }
    }

    /**
     * The picker sits behind the entries tab, which the submenu shows for `Section::allowsEntries()`.
     */
    public function testEntriesRefusesASectionWithoutEntries(): void
    {
        $this->login();
        $section = $this->createSection('Linking');

        self::assertFalse($section->allowsEntries());

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/section/entries', ['id' => $section->id]);
    }

    private function createEntry(string $name, string $slug): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function createSection(string $name): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = $name;
        $section->populateEntryRelation($this->entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }

    private function setSectionSets(): void
    {
        Section::getModule()->setSectionSets(fn (): array => [
            SectionSet::make(1)
                ->name('Landing page')
                ->sections(
                    SectionTemplate::make(Section::TYPE_DEFAULT)->attribute('name', 'Hero'),
                    SectionTemplate::make(Section::TYPE_DEFAULT)->attribute('name', 'Text'),
                ),
        ]);
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = [], bool $reload = false): mixed
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
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
