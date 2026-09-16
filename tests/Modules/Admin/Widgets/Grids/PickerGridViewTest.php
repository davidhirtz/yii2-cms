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
 * A picker lists records to choose from, so nothing in a row may lead out of it — neither the record's own page,
 * which is an external link button opening in a new tab, nor the related lists the count badges carry. The file
 * picker is pinned here too: the cms asset controller is the only place the media grid renders with a model.
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

        // The subentry badge still drills into the picker; the section and asset counts lead out of it.
        self::assertStringContainsString('<a class="badge" href="/admin/cms/section-entry/create?parent=1">2</a>', $html);
        self::assertStringContainsString('<div class="badge">5</div>', $html);
        self::assertStringContainsString('<div class="badge">2</div>', $html);
    }

    /**
     * The grid that picks the entry a section is moved or copied to.
     */
    public function testTheSectionParentEntryPickerDrillsIntoTheSubentries(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/section/entries', ['id' => 3]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<a class="strong" href="/admin/cms/section/entries?parent=1">Test Page – Enabled</a>',
            $html,
        );
        self::assertStringContainsString('<div class="strong">Test Page – Disabled</div>', $html);
        self::assertStringContainsString('<div class="badge">5</div>', $html);

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

        // The branch badge still drills into the picker; the entry count leads out of it.
        self::assertStringContainsString('<a class="badge" href="/admin/cms/entry-category/index?category=1">1</a>', $html);
        self::assertStringContainsString('<div class="badge">2</div>', $html);
    }

    /**
     * A subcategory is listed out of the context of its branch, so the picker always renders its path.
     */
    public function testTheCategoryPickerShowsTheCategoryPath(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1, 'category' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<div class="strong">Child category 1</div><div class="small">Root category 1</div>',
            $html,
        );
    }

    /**
     * The picker lists every category in the installation, so it needs the search the category index has —
     * `EntryCategoryController::actionIndex()` has always taken the `q` parameter, only the input was missing.
     */
    public function testTheCategoryPickerHasASearchInput(): void
    {
        $this->login();

        // The grid reads the keyword off the request, the controller off its action parameter.
        $this->getWebRequest()->setQueryParams(['entry' => 1, 'q' => 'Child']);
        $html = Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1, 'q' => 'Child']);

        self::assertIsString($html);
        self::assertStringContainsString('name="q" value="Child"', $html);
        self::assertStringContainsString('<mark>Child</mark> category 1', $html);
        self::assertStringNotContainsString('Root category 2', $html);
    }

    /**
     * The picker hangs off an entry, and a GET form submits its own fields and nothing else — so the entry the
     * search would otherwise leave behind is a hidden input.
     */
    public function testTheCategoryPickerSearchKeepsTheEntry(): void
    {
        $this->login();

        $this->getWebRequest()->setQueryParams(['entry' => 1, 'category' => 1]);
        $html = Yii::$app->runAction('admin/cms/entry-category/index', ['entry' => 1, 'category' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('action="/admin/cms/entry-category/index" method="get"', $html);
        self::assertStringContainsString('<input type="hidden" name="entry" value="1">', $html);
        self::assertStringContainsString('<input type="hidden" name="category" value="1">', $html);
    }

    public function testTheFilePickerLinksToTheFileOnlyThroughItsButton(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1]);

        self::assertIsString($html);

        // Neither the thumbnail nor the name nor the alt text check leads to the file any more.
        self::assertStringContainsString('<div class="strong">Test 1</div>', $html);
        self::assertStringContainsString('<span class="text-success fas fa-check"></span>', $html);
        self::assertStringContainsString('<div class="badge">1</div>', $html);

        $this->assertRecordLinksOpenInANewTab($html, '/admin/media/file/update');
    }

    /**
     * A picker must not change what it lists — the flow the user is in is picking, not editing — so the status icon
     * stays an icon there and is the cycle button only on the index.
     */
    public function testTheStatusIconCyclesOnTheIndexButNotInAPicker(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('hx-post="/admin/cms/entry/status?id=1"', $html);

        $html = Yii::$app->runAction('admin/cms/section-entry/create', ['section' => 3]);

        self::assertIsString($html);
        self::assertStringNotContainsString('/admin/cms/entry/status', $html);
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
        self::assertStringContainsString('<a class="badge" href="/admin/cms/section/index?entry=1">5</a>', $html);
    }

    /**
     * The grid rows only: the breadcrumbs above them lead to the record on purpose.
     */
    private function assertRecordLinksOpenInANewTab(string $html, string $route): void
    {
        self::assertSame(1, preg_match('~<tbody.*?</tbody>~s', $html, $rows));

        preg_match_all('~<a\b[^>]*>~', $rows[0] ?? '', $matches);
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
