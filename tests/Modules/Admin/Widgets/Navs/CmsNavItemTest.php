<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsNavItem;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;
use yii\helpers\Url;

class CmsNavItemTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$container->setDefinitions([
            CmsNavItem::class => ['showEntryTypes' => true],
            Entry::class => TestEntry::class,
        ]);

        Entry::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Entry::instance(true);
        parent::tearDown();
    }

    /**
     * Each type is an item of its own, and the parent keeps its label and link: the setters used to chain onto the
     * return of `addItem()`, the parent, so every type overwrote it and the items had neither.
     */
    public function testEachTypeLinksToItsOwnList(): void
    {
        $this->login();

        $html = CmsNavItem::make()->render();

        self::assertStringContainsString('>' . Yii::t('cms', 'COMMON_ENTRIES') . '</span>', $html);
        self::assertStringContainsString('href="' . Url::to(['/admin/cms/entry/index']) . '"', $html);

        foreach ([TestEntry::TYPE_PAGE => 'Page', TestEntry::TYPE_POST => 'Post'] as $type => $label) {
            $url = Url::to(['/admin/cms/entry/index', 'type' => $type]);
            self::assertMatchesRegularExpression('~href="' . preg_quote($url, '~') . '"><span class="nav-link-label">' . $label . '<~', $html);
        }
    }

    public function testNoTypeIsActiveOutsideTheEntries(): void
    {
        $this->login();

        self::assertSame([], $this->getActiveTypes(CmsNavItem::make()->render()));
    }

    public function testTheListedTypeIsActive(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry/index', ['type' => TestEntry::TYPE_POST]);

        self::assertIsString($html);
        self::assertSame([TestEntry::TYPE_POST], $this->getActiveTypes($html));
    }

    public function testAnEntrysTypeIsActiveOnItsSectionPages(): void
    {
        $this->login();
        $entry = $this->getEntryFromFixture('page-enabled');

        $html = Yii::$app->runAction('admin/cms/section/index', ['entry' => $entry->id]);

        self::assertIsString($html);
        self::assertSame([TestEntry::TYPE_PAGE], $this->getActiveTypes($html));
    }

    public function testASectionsEntryTypeIsActiveOnTheSectionForm(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertSame([$section->entry->type], $this->getActiveTypes($html));
    }

    /**
     * @return list<int>
     */
    private function getActiveTypes(string $html): array
    {
        preg_match_all('~<a class="nav-link active" href="[^"]*[?&](?:amp;)?type=(\d+)"~', $html, $matches);
        return array_map(intval(...), $matches[1]);
    }

    private function login(): void
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');
        $user = User::findOne($fixture->data['admin']['id']);
        self::assertInstanceOf(User::class, $user);

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(Entry::AUTH_ENTRY), $user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
