<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SetupController;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * `SetupController` is abstract — a project subclasses it and supplies the content a fresh installation starts
 * with. It runs once, so a failure there is easy to miss: every insert is logged rather than thrown.
 */
class SetupControllerTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = Entry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;

        // `Entry::validateParentId()` drops the parent while nesting is off, so a sub-entry would become a root one
        $module->enableNestedEntries = true;

        $cms = Yii::$app->getModule('admin')->getModule('cms');
        $cms->controllerMap['setup'] = TestSetupController::class;
    }

    public function testTheContentIsCreatedAndTheAdminIsSentToTheEntries(): void
    {
        $this->login();

        $response = Yii::$app->runAction('admin/cms/setup/index');

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('cms/entry/index', (string)$response->getHeaders()->get('location'));

        self::assertSame(1, (int)Category::find()->where(['slug' => 'news'])->count());

        $entry = Entry::findOne(['name' => 'Home']);

        self::assertNotNull($entry);
        self::assertSame(Entry::STATUS_ENABLED, $entry->status);
    }

    public function testAnEntryCarriesItsSectionsCategoriesAndChildren(): void
    {
        $this->login();
        Yii::$app->runAction('admin/cms/setup/index');

        $entry = Entry::findOne(['name' => 'Home']);
        $category = Category::findOne(['slug' => 'news']);

        self::assertSame(1, (int)Section::find()->where(['entry_id' => $entry->id])->count());
        self::assertNotNull(EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $category->id]));

        $child = Entry::findOne(['name' => 'About']);

        self::assertNotNull($child);
        self::assertSame($entry->id, $child->parent_id);
    }

    public function testTheDefaultFolderIsThere(): void
    {
        $this->login();
        Yii::$app->runAction('admin/cms/setup/index');

        self::assertFalse(FolderCollection::getDefault()->getIsNewRecord());
    }

    /**
     * The action is reachable from the dashboard, so a second visit must not seed the content twice.
     */
    public function testASecondRunAddsNothing(): void
    {
        $this->login();

        Yii::$app->runAction('admin/cms/setup/index');

        $categories = (int)Category::find()->count();
        $entries = (int)Entry::find()->count();

        Yii::$app->runAction('admin/cms/setup/index');

        self::assertSame($categories, (int)Category::find()->count());
        self::assertSame($entries, (int)Entry::find()->count());
    }

    public function testAnEntryThatCannotBeSavedIsLoggedRatherThanThrown(): void
    {
        $this->login();
        $this->logger->isRecording = true;

        TestSetupController::$entryAttributes = [
            ['status' => Entry::STATUS_ENABLED, 'type' => Entry::TYPE_DEFAULT, 'name' => ''],
        ];

        Yii::$app->runAction('admin/cms/setup/index');

        self::assertSame(0, (int)Entry::find()->count());

        $messages = array_filter(
            $this->logger->messages,
            fn (array $message): bool => is_string($message[0]) && str_contains($message[0], 'Entry record')
        );

        self::assertNotEmpty($messages);
    }

    public function testTheSetupIsForbiddenForANonAdmin(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/setup/index');
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}

class TestSetupController extends SetupController
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    public static ?array $entryAttributes = null;

    /**
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function getCategoryAttributes(): array
    {
        return [
            [
                'status' => Category::STATUS_ENABLED,
                'name' => 'News',
                'slug' => 'news',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function getEntryAttributes(): array
    {
        return self::$entryAttributes ?? [
            [
                'status' => Entry::STATUS_ENABLED,
                'type' => Entry::TYPE_DEFAULT,
                'name' => 'Home',
                'slug' => 'home',
                // the categories are inserted first, so the entry configuration can name one by its slug
                'categories' => [
                    ['category_id' => Category::findOne(['slug' => 'news'])?->id],
                ],
                'sections' => [
                    [
                        'status' => Section::STATUS_ENABLED,
                        'type' => Section::TYPE_DEFAULT,
                        'name' => 'Intro',
                    ],
                ],
                'entries' => [
                    [
                        'status' => Entry::STATUS_ENABLED,
                        'type' => Entry::TYPE_DEFAULT,
                        'name' => 'About',
                        'slug' => 'about',
                    ],
                ],
            ],
        ];
    }
}
