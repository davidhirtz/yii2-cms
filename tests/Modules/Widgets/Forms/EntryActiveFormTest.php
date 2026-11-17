<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Yii;

class EntryActiveFormTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTenantFieldIsRenderedLastForASingleTenant(): void
    {
        Tenant::deleteAll(['!=', 'id', TenantCollection::getDefault()->id]);
        TenantCollection::invalidateCache();

        $content = $this->render();

        self::assertGreaterThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'name="Entry[tenant_id]"'),
            'The tenant field is not rendered after the name field.'
        );
    }

    public function testTenantFieldIsRenderedFirstForSeveralTenants(): void
    {
        $content = $this->render();

        self::assertLessThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'name="Entry[tenant_id]"'),
            'The tenant field is not rendered before the name field.'
        );
    }

    public function testNoIdIsRenderedTwice(): void
    {
        preg_match_all('/ id="([^"]+)"/', $this->render(), $matches);
        $ids = $matches[1];

        self::assertNotSame([], $ids);
        self::assertSame(array_values(array_unique($ids)), $ids);
    }

    public function testSlugBaseUrlIsTheParentPathForAParentWithoutARoute(): void
    {
        $parent = $this->getEntryFromFixture('page-disabled');
        self::assertFalse($parent->hasRoute());

        $entry = TestEntry::create();
        $entry->parent_id = $parent->id;

        self::assertStringContainsString(
            $parent->getFormattedSlug() . '/',
            $this->render($entry),
            'The slug base URL does not carry the path of a parent that has no route of its own.'
        );
    }

    /**
     * The admin runs under the default tenant while it edits an entry of the German one.
     */
    public function testAnEntryOfAnotherTenantFollowsItsOwnTenant(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);
        Yii::$container->set(TestEntry::class, ['i18nAttributes' => ['name', 'slug']]);
        TestEntry::instance(true);

        try {
            $german = Tenant::findOne(2) ?? self::fail('No German tenant.');
            $german->updateAttributes(['languages' => []]);
            TenantCollection::invalidateCache();

            $manager = Yii::$app->getUrlManager();
            self::assertInstanceOf(UrlManager::class, $manager);
            $manager->tenant = TenantCollection::getDefault();
            $manager->setContentLanguage('fr');

            // The tenant's first entry is its index, which has no slug field.
            $index = TestEntry::create();
            $index->tenant_id = $german->id;
            $index->setAttribute('name_de', 'Startseite Å 東京');
            self::assertTrue($index->insert(), implode(' ', $index->getErrorSummary(true)));

            $entry = TestEntry::create();
            $entry->tenant_id = $german->id;

            $content = $this->render($entry);

            self::assertStringContainsString('name="Entry[name_de]"', $content);
            self::assertStringNotContainsString('name="Entry[name]"', $content);
            self::assertStringNotContainsString('name="Entry[name_fr]"', $content);
            self::assertStringContainsString('title="https://draft.domain.localhost/de/"', $content);
            self::assertSame($german->id, EntryLinks::findTenantId($entry));
        } finally {
            Yii::$container->clear(TestEntry::class);
            TestEntry::instance(true);
        }
    }

    protected function render(?TestEntry $entry = null): string
    {
        return EntryActiveForm::make()
            ->model($entry ?? TestEntry::create())
            ->render();
    }
}
