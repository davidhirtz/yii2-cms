<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\File;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetSubmenuItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;

/**
 * Nothing in a picker row may lead out of it; the file picker is pinned here, the only place the media grid renders
 * with a model.
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
            '<div class="strong">Child category 1</div><div class="small"><a href="/admin/cms/entry-category/index?category=1">Root category 1</a></div>',
            $html,
        );
    }

    /**
     * The picker lists every category, and `EntryCategoryController::actionIndex()` has always taken the `q` parameter.
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
     * A GET form submits its own fields alone, so the entry the picker hangs off is a hidden input.
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
        $user = $this->login();
        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(File::AUTH_FILE), $user->id);
        $this->getWebUser()->setIdentity($user);
        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1]);

        self::assertIsString($html);

        // Neither the thumbnail nor the name nor the alt text check leads to the file any more.
        self::assertStringContainsString('<div class="strong">Test 1</div>', $html);
        self::assertStringContainsString('<span class="text-success fas fa-check"></span>', $html);
        self::assertStringContainsString('<div class="badge">1</div>', $html);

        $this->assertRecordLinksOpenInANewTab($html, '/admin/media/file/update');
    }

    /**
     * The grid alone is swapped so a click on the thirtieth row stays there, and the submenu's count in `hx-select-oob`
     * replaces the body's own, so the flashes are named beside it.
     */
    public function testTheFilePickerToggleCarriesTheFilterAndSwapsTheGridAlone(): void
    {
        $this->login();

        $this->getWebRequest()->setQueryParams(['entry' => 1, 'folder' => 1, 'q' => 'Test']);
        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1, 'folder' => 1, 'q' => 'Test']);

        self::assertIsString($html);

        $attributes = 'hx-select="#' . FileGridView::ID . '" hx-swap="outerHTML" hx-target="#' . FileGridView::ID
            . '" hx-select-oob="#flashes:beforeend,#' . AssetSubmenuItem::ID . '"';

        // The file the entry already has is removed, the one it does not is added.
        self::assertStringContainsString(
            $attributes . ' hx-post="/admin/cms/entry-asset/remove?entry=1&amp;file=1&amp;folder=1&amp;q=Test"',
            $html,
        );

        self::assertStringContainsString(
            $attributes . ' hx-post="/admin/cms/entry-asset/create?entry=1&amp;file=3&amp;folder=1&amp;q=Test"',
            $html,
        );
    }

    /**
     * Replacing an asset's file leads to that asset's own page, so its button keeps the body's whole-page swap.
     */
    public function testTheFileReplacementButtonSwapsTheWholePage(): void
    {
        $this->login();
        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1, 'asset' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('hx-post="/admin/cms/entry-asset/create?entry=1&amp;file=3&amp;asset=1"', $html);
        self::assertStringNotContainsString('hx-select="#' . FileGridView::ID . '" hx-swap', $html);
    }

    /**
     * A picker must not change what it lists, so its status icon is no cycle button.
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
     * `CmsFixtureTrait` declares no accessor for the user fixture, since one would collide with `UserFixtureTrait`.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
