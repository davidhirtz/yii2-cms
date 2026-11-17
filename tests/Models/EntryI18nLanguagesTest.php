<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

class EntryI18nLanguagesTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The query layer reads `i18nAttributes` off the shared instance(), so it has to be configured.
        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name', 'slug']],
            TestEntry::class => ['i18nAttributes' => ['name', 'slug']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);
        Yii::$container->clear(TestEntry::class);

        Entry::instance(true);
        TestEntry::instance(true);

        parent::tearDown();
    }

    public function testAnEntryOffersTheLanguagesOfItsTenant(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->populateTenantRelation($this->createTenant('en-US', ['de']));

        self::assertSame(['en-US', 'de'], $entry->getI18nLanguages());

        $section = TestSection::create();
        $section->populateEntryRelation($entry);
        self::assertSame(['en-US', 'de'], $section->getI18nLanguages());

        $asset = EntryAsset::create();
        $asset->populateModelRelation($entry);
        self::assertSame(['en-US', 'de'], $asset->getI18nLanguages());
    }

    public function testATenantWithoutTheSourceLanguageOffersItsOwnDefaultFirst(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->populateTenantRelation($this->createTenant('fr', ['de']));

        self::assertSame(['fr', 'de'], $entry->getI18nLanguages());
    }

    public function testTheSourceColumnsCopyTheDefaultLanguageOfATenantWithoutIt(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);
        $tenant = $this->createTenant('fr', ['de']);

        $entry = TestEntry::create();
        $entry->tenant_id = $tenant->id;
        $entry->setAttribute('name_fr', 'Château Å 東京 🚀');
        $entry->setAttribute('name_de', 'Mu' . "\u{308}" . 'ller');

        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry = TestEntry::findOne($entry->id) ?? self::fail('No entry.');
        self::assertSame('Château Å 東京 🚀', $entry->name);
        self::assertSame($entry->getI18nAttribute('slug', 'fr'), $entry->slug);

        $entry->name = 'Stale';
        $entry->setAttribute('name_fr', 'Привет');

        self::assertSame(1, $entry->update());
        self::assertSame('Привет', TestEntry::findOne($entry->id)?->name);
    }

    public function testCodeWritingTheSourceColumnAloneKeepsIt(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->tenant_id = $this->createTenant('fr', [])->id;
        $entry->name = 'Ågot 東京';

        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));
        self::assertSame('Ågot 東京', $entry->name);
        self::assertSame('Ågot 東京', $entry->getI18nAttribute('name', 'fr'));
    }

    public function testClearingTheDefaultLanguageIsAnErrorOnIt(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->tenant_id = $this->createTenant('fr', [])->id;
        $entry->setAttribute('name_fr', 'Château');

        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry->setAttribute('name_fr', '');

        self::assertFalse($entry->validate());
        self::assertTrue($entry->hasErrors('name_fr'));
    }

    public function testATenantOfferingEveryLanguageNarrowsNothing(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->populateTenantRelation($this->createTenant('en-US', null));

        self::assertSame(['en-US', 'de', 'fr'], $entry->getI18nLanguages());
        self::assertSame(['en-US', 'de', 'fr'], TestSection::create()->getI18nLanguages());
    }

    /**
     * @param list<string>|null $languages
     */
    protected function createTenant(string $language, ?array $languages): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Languages';
        $tenant->url = 'https://www.languages.localhost';
        $tenant->language = $language;
        $tenant->languages = $languages;

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));
        TenantCollection::invalidateCache();

        return $tenant;
    }
}
