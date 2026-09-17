<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Web\View;
use Yii;

class SectionHeaderTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheTitleStaysOnTheEntry(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h1><a href="/admin/cms/entry/update?id=' . $section->entry_id . '">'
            . $section->entry->getAdminName() . '</a></h1>',
            $html,
        );
    }

    public function testTheSubtitleNamesTheSectionByItsPositionAndLinksToIt(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h2 class="header-subtitle"><a class="header-subtitle-item"'
            . ' href="/admin/cms/section/update?id=' . $section->id . '">'
            . Yii::t('skeleton', 'COMMON_MODEL_ID', [
                'model' => Yii::t('cms', 'COMMON_SECTION'),
                'id' => $section->position,
            ]) . '</a></h2>',
            $html,
        );
    }

    /**
     * An entry filed under another entry is still its own base, so the title never climbs to the ancestor.
     */
    public function testTheTitleOfASectionOfANestedEntryIsThatEntry(): void
    {
        $this->login();

        $entry = $this->getEntryFromFixture('post-1');
        $section = TestSection::instantiateByType(TestSection::TYPE_HEADLINE);
        $section->entry_id = $entry->id;

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h1><a href="/admin/cms/entry/update?id=' . $entry->id . '">' . $entry->getAdminName() . '</a></h1>',
            $html,
        );
        self::assertStringNotContainsString($entry->parent->getAdminName() . '</a></h1>', $html);
    }

    public function testTheSubheadingIsTheSectionsFrontendUrl(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString('#' . $section->getHtmlId() . '" target="_blank"', $html);
    }

    public function testTheBreadcrumbsAreEntriesEntrySections(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertSame(
            [
                Yii::t('cms', 'COMMON_ENTRIES'),
                $section->entry->getAdminName(),
                Yii::t('cms', 'COMMON_SECTIONS'),
            ],
            $this->getBreadcrumbLabels(),
        );
    }

    /**
     * @return list<string>
     */
    private function getBreadcrumbLabels(): array
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return array_values(array_map(
            static fn (Breadcrumb $breadcrumb): string => $breadcrumb->label,
            $view->getBreadcrumbs(),
        ));
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(Entry::AUTH_ENTRY), $user->id);

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
