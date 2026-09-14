<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;

/**
 * A picker lists records to choose from, so nothing in a row may lead to the record's own page in the same tab —
 * that cancels the very flow the user is in. The file picker is pinned here too: the cms asset controller is the
 * only place the media grid renders with a model.
 */
class PickerGridViewTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableSectionEntries = true;
        $module->enableNestedEntries = true;
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
    }

    public function testTheEntryPickerDrillsIntoTheSubentries(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => 3]);

        self::assertIsString($html);

        // The entry with subentries, and one without.
        self::assertStringContainsString(
            '<a class="strong" href="/admin/cms/section-entry/create?parent=1">Test Page – Enabled</a>',
            $html,
        );
        self::assertStringContainsString('<div class="strong">Test Page – Disabled</div>', $html);

        $this->assertRecordLinksOpenInANewTab($html, '/admin/cms/entry/update');
    }

    public function testTheCategoryPickerDrillsIntoTheSubcategories(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1]);

        self::assertIsString($html);

        // The category with branches, and one without.
        self::assertStringContainsString(
            '<a class="strong" href="/admin/cms/entry-category/index?category=1">Root category 1</a>',
            $html,
        );
        self::assertStringContainsString('<div class="strong">Root category 2</div>', $html);

        $this->assertRecordLinksOpenInANewTab($html, '/admin/cms/category/update');
    }

    public function testTheFilePickerLinksToTheFileOnlyThroughItsButton(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1]);

        self::assertIsString($html);

        // Neither the thumbnail nor the name nor the alt text check leads to the file any more.
        self::assertStringContainsString('<div class="strong">Test 1</div>', $html);
        self::assertStringContainsString('<span class="text-success fas fa-check"></span>', $html);

        $this->assertRecordLinksOpenInANewTab($html, '/admin/media/file/update');
    }

    /**
     * The index is not a picker, so its rows lead to the record as they always did.
     */
    public function testTheEntryIndexKeepsItsLinks(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('<a class="strong" href="/admin/cms/entry/update?id=1">', $html);
    }

    /**
     * The grid rows only: the breadcrumbs above them lead to the record on purpose.
     */
    private function assertRecordLinksOpenInANewTab(string $html, string $route): void
    {
        self::assertSame(1, preg_match('~<tbody.*?</tbody>~s', $html, $rows));

        preg_match_all('~<a\b[^>]*>~', $rows[0], $matches);
        $links = array_filter($matches[0], static fn (string $link): bool => str_contains($link, $route));

        self::assertNotEmpty($links);

        foreach ($links as $link) {
            self::assertStringContainsString('target="_blank"', $link);
        }
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $auth = Yii::$app->getAuthManager();

        foreach ([TestEntry::AUTH_ENTRY, Category::AUTH_CATEGORY] as $permission) {
            $auth->assign($auth->getPermission($permission), $user->id);
        }

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
