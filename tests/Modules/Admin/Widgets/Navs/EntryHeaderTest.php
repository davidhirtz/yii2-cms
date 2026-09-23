<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Web\View;
use Yii;

class EntryHeaderTest extends TestCase
{
    use CmsFixtureTrait;

    public function testAChildEntrysBarReadsEntriesParentSubentries(): void
    {
        $this->login();
        $entry = $this->getEntryFromFixture('post-1');

        $html = Yii::$app->runAction('admin/cms/entry/update', ['id' => $entry->id]);

        self::assertIsString($html);
        self::assertSame(
            [
                Yii::t('cms', 'COMMON_ENTRIES'),
                $entry->parent->getAdminName(),
                Yii::t('cms', 'COMMON_SUBENTRIES'),
            ],
            $this->getBreadcrumbLabels(),
        );
    }

    public function testARootEntrysBarReadsEntriesAlone(): void
    {
        $this->login();
        $entry = $this->getEntryFromFixture('page-enabled');

        $html = Yii::$app->runAction('admin/cms/entry/update', ['id' => $entry->id]);

        self::assertIsString($html);
        self::assertSame([Yii::t('cms', 'COMMON_ENTRIES')], $this->getBreadcrumbLabels());
        self::assertStringNotContainsString('class="header-path small"', $html);
    }

    public function testTheIndexPageForAParentStillRendersTheParentsHeader(): void
    {
        $this->login();
        $entry = $this->getEntryFromFixture('page-enabled');

        $html = Yii::$app->runAction('admin/cms/entry/index', ['parent' => $entry->id]);

        self::assertIsString($html);
        self::assertStringContainsString('>' . $entry->getAdminName() . '</a></h1>', $html);
        self::assertSame([Yii::t('cms', 'COMMON_ENTRIES')], $this->getBreadcrumbLabels());
    }

    public function testTheIndexPageForAParentIsTitledWithItsNameInAnotherLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name']],
            TestEntry::class => ['i18nAttributes' => ['name']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);

        try {
            $this->login()->language = 'de';
            $translated = $this->getEntryFromFixture('page-enabled');
            $translated->setAttributes(['name_de' => 'Seite'], false);
            self::assertSame(1, $translated->update(), implode(' ', $translated->getErrorSummary(true)));

            $untranslated = $this->getEntryFromFixture('page-disabled');

            foreach (['Seite' => $translated, $untranslated->name => $untranslated] as $name => $entry) {
                $html = Yii::$app->runAction('admin/cms/entry/index', ['parent' => $entry->id]);

                self::assertIsString($html);
                self::assertStringContainsString('>' . $name . '</a></h1>', $html);
            }
        } finally {
            Yii::$container->clear(Entry::class);
            Yii::$container->clear(TestEntry::class);

            Entry::instance(true);
            TestEntry::instance(true);
        }
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
