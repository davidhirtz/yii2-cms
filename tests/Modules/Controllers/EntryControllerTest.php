<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntryControllerTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Entry::getModule()->enableNestedEntries = true;
    }

    public function testIndexListsTheRootEntries(): void
    {
        $this->login();

        $parent = $this->createEntry('Parent', 'parent');
        $this->createEntry('Child', 'child', $parent);

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('Parent', $html);
        self::assertStringNotContainsString('>Child<', $html);
    }

    public function testIndexListsTheChildrenOfAParent(): void
    {
        $this->login();

        $parent = $this->createEntry('Parent', 'parent');
        $this->createEntry('Child', 'child', $parent);

        $html = Yii::$app->runAction('admin/cms/entry/index', ['parent' => $parent->id]);

        self::assertIsString($html);
        self::assertStringContainsString('Child', $html);
    }

    public function testIndexSearchesTheName(): void
    {
        $this->login();

        $this->createEntry('Needle', 'needle');
        $this->createEntry('Haystack', 'haystack');

        $html = Yii::$app->runAction('admin/cms/entry/index', ['q' => 'Needl']);

        self::assertIsString($html);
        self::assertStringContainsString('Needle', $html);
        self::assertStringNotContainsString('Haystack', $html);
    }

    public function testIndexRedirectsToTheDefaultEntryType(): void
    {
        $this->login();
        Entry::getModule()->defaultEntryType = Entry::TYPE_DEFAULT;

        $response = Yii::$app->runAction('admin/cms/entry/index');

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('type=' . Entry::TYPE_DEFAULT, (string)$response->getHeaders()->get('location'));
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/entry/index');
    }

    public function testCreateRendersTheForm(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry/create');

        self::assertIsString($html);
        self::assertStringContainsString('name="Entry[name]"', $html);
    }

    public function testCreateInsertsTheEntryUnderTheGivenParent(): void
    {
        $this->login();
        $parent = $this->createEntry('Parent', 'parent');

        $response = $this->post('admin/cms/entry/create', ['parent' => $parent->id], [
            'Entry' => [
                'status' => Entry::STATUS_ENABLED,
                'name' => 'A new entry',
                'slug' => 'a-new-entry',
            ],
        ]);

        self::assertInstanceOf(Response::class, $response);

        $entry = Entry::findOne(['name' => 'A new entry']);

        self::assertNotNull($entry);
        self::assertSame($parent->id, $entry->parent_id);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testAFormReloadDoesNotSave(): void
    {
        $this->login();

        $html = $this->post('admin/cms/entry/create', [], [
            'Entry' => [
                'status' => Entry::STATUS_ENABLED,
                'name' => 'Not saved',
                'slug' => 'not-saved',
            ],
        ], reload: true);

        self::assertIsString($html);
        self::assertNull(Entry::findOne(['name' => 'Not saved']));
    }

    public function testUpdateSavesTheEntry(): void
    {
        $this->login();
        $entry = $this->createEntry('Original', 'original');

        $response = $this->post('admin/cms/entry/update', ['id' => $entry->id], [
            'Entry' => [
                'status' => Entry::STATUS_ENABLED,
                'name' => 'Renamed',
                'slug' => 'original',
            ],
        ]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('Renamed', Entry::findOne($entry->id)->name);
    }

    public function testUpdateOfAnUnknownEntryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry/update', ['id' => 99999]);
    }

    public function testUpdateAllChangesTheSelectedEntries(): void
    {
        $this->login();

        $first = $this->createEntry('First', 'first');
        $second = $this->createEntry('Second', 'second');
        $third = $this->createEntry('Third', 'third');

        $this->post('admin/cms/entry/update-all', [], [
            'selection' => [(string)$first->id, (string)$second->id],
            'Entry' => ['status' => Entry::STATUS_DISABLED],
        ]);

        self::assertSame(Entry::STATUS_DISABLED, Entry::findOne($first->id)->status);
        self::assertSame(Entry::STATUS_DISABLED, Entry::findOne($second->id)->status);
        self::assertSame(Entry::STATUS_ENABLED, Entry::findOne($third->id)->status);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testDuplicateCopiesTheEntry(): void
    {
        $this->login();
        $entry = $this->createEntry('Original', 'original');

        $response = $this->post('admin/cms/entry/duplicate', ['id' => $entry->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(2, (int)Entry::find()->where(['name' => 'Original'])->count());
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testReplaceIndexMakesTheEntryTheHomePage(): void
    {
        $this->login();

        $previous = $this->createEntry('Old home', 'home');
        $entry = $this->createEntry('New home', 'new-home');

        $response = $this->post('admin/cms/entry/replace-index', ['id' => $entry->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue(Entry::findOne($entry->id)->isIndex());
        self::assertSame(Entry::STATUS_DISABLED, Entry::findOne($previous->id)->status);
    }

    public function testDeleteRemovesTheEntry(): void
    {
        $this->login();
        $entry = $this->createEntry('Doomed', 'doomed');

        $response = $this->post('admin/cms/entry/delete', ['id' => $entry->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(Entry::findOne($entry->id));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();
        $entry = $this->createEntry('Doomed', 'doomed');

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/entry/delete', ['id' => $entry->id]);
    }

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $first = $this->createEntry('First', 'first');
        $second = $this->createEntry('Second', 'second');

        $html = $this->post('admin/cms/entry/order', [], [
            'entry' => [$second->id, $first->id],
        ]);

        self::assertIsString($html);
        self::assertLessThan(
            Entry::findOne($first->id)->position,
            Entry::findOne($second->id)->position
        );
    }

    private function createEntry(string $name, string $slug, ?Entry $parent = null): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->populateParentRelation($parent);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = [], bool $reload = false): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
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

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
