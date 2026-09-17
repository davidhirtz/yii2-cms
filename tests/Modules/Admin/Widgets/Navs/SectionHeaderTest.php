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

    public function testTheTitleIsTheSection(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h1><a href="/admin/cms/section/update?id=' . $section->id . '">' . $section->getAdminName() . '</a></h1>',
            $html,
        );
        self::assertStringNotContainsString($section->entry->name . '</a></h1>', $html);
    }

    public function testTheTitleFallsBackToTheModelId(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-entry-draft');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '>' . Yii::t('skeleton', 'COMMON_MODEL_ID', [
                'model' => $section->getAdminType(),
                'id' => $section->id,
            ]) . '</a></h1>',
            $html,
        );
    }

    public function testThePathHoldsTheEntry(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString('class="header-path small"', $html);
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/entry/update?id=' . $section->entry_id . '">',
            $html,
        );
    }

    public function testThePathOfANestedEntryHoldsBothEntries(): void
    {
        $this->login();

        $entry = $this->getEntryFromFixture('post-1');
        $section = TestSection::instantiateByType(TestSection::TYPE_HEADLINE);
        $section->entry_id = $entry->id;

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/entry/update?id=' . $entry->parent_id . '">',
            $html,
        );
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/entry/update?id=' . $entry->id . '">',
            $html,
        );
    }

    public function testTheBreadcrumbsAreEntriesEntrySections(): void
    {
        $this->login();
        $section = $this->getSectionFromFixture('section-headline');
        $entry = $section->entry;

        $html = Yii::$app->runAction('admin/cms/section/update', ['id' => $section->id]);

        self::assertIsString($html);

        $crumbs = $this->getBreadcrumbLabels();
        self::assertSame(
            [Yii::t('cms', 'COMMON_ENTRIES'), $entry->getAdminName(), Yii::t('cms', 'COMMON_SECTIONS')],
            $crumbs,
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
