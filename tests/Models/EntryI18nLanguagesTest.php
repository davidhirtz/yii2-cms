<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Search;
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

        $entry->setAttribute('name_fr', 'Привет');

        self::assertSame(1, $entry->update());
        self::assertSame('Château Å 東京 🚀', TestEntry::findOne($entry->id)?->name, 'A filled source column is never overwritten.');
    }

    public function testAnEntryMovedToATenantWithoutTheSourceLanguageKeepsItsSourceContent(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $entry = TestEntry::create();
        $entry->tenant_id = $this->createTenant('en-US', ['de'])->id;
        $entry->name = 'English Å 東京';
        $entry->setAttribute('name_de', 'Deutsch Mu' . "\u{308}" . 'ller');

        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry = TestEntry::findOne($entry->id) ?? self::fail('No entry.');
        $entry->tenant_id = $this->createTenant('de', [])->id;

        self::assertSame(1, $entry->update(), implode(' ', $entry->getErrorSummary(true)));
        self::assertSame('English Å 東京', TestEntry::findOne($entry->id)?->name);
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

    public function testAClearedDefaultLanguageTakesTheSourceValueBackLikeATranslation(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->tenant_id = $this->createTenant('fr', [])->id;
        $entry->setAttribute('name_fr', 'Château');

        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry->setAttribute('name_fr', '');

        self::assertTrue($entry->validate());
        self::assertSame('Château', $entry->getI18nAttribute('name', 'fr'));
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
     * The form shows the tenant's languages alone, and the editor may show fewer still: the save writes what was posted.
     */
    public function testASaveFromANarrowedFormKeepsTheOtherLanguages(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);
        $tenant = $this->createTenant('en-US', ['de']);

        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->tenant_id = $tenant->id;
        $entry->setAttributes(['name' => 'Index', 'slug' => '', 'name_de' => 'Start', 'slug_de' => '', 'name_fr' => 'Accueil'], false);
        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->tenant_id = $tenant->id;
        $entry->setAttributes([
            'name' => 'Castle',
            'slug' => 'castle',
            'name_de' => 'Schloss',
            'slug_de' => 'schloss',
            'name_fr' => 'Château Å',
            'slug_fr' => 'chateau',
        ], false);
        self::assertTrue($entry->insert(), implode(' ', $entry->getErrorSummary(true)));

        $entry = TestEntry::findOne($entry->id) ?? self::fail('Not found.');
        $posted = array_fill_keys($entry->getI18nAttributesNames(['name', 'slug'], $entry->getI18nLanguages()), '');
        self::assertSame(['name', 'name_de', 'slug', 'slug_de'], array_keys($posted));

        self::assertTrue($entry->load([...$posted, 'name' => 'Castle 東京', 'slug' => 'castle', 'name_de' => 'Burg', 'slug_de' => 'burg'], ''));
        self::assertTrue($entry->update() !== false, implode(' ', $entry->getErrorSummary(true)));

        $entry = TestEntry::findOne($entry->id) ?? self::fail('Not found.');
        self::assertSame('Château Å', $entry->getI18nAttribute('name', 'fr'));
        self::assertSame('Burg', $entry->getI18nAttribute('name', 'de'));

        $permalinks = Permalink::find()->select(['slug', 'language'])->where(['entry_id' => $entry->id])->indexBy('language')->column();
        self::assertSame('chateau', $permalinks['fr'] ?? null);
        self::assertSame('burg', $permalinks['de'] ?? null);

        $languages = Search::find()->select('language')->where(['model_class' => Entry::class, 'model_id' => $entry->id])->column();
        self::assertContains('fr', $languages);
    }

    /**
     * @param list<string>|null $languages
     */
    protected function createTenant(string $language, ?array $languages): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Languages';
        $tenant->url = 'https://www.' . strtolower($language) . '.localhost';
        $tenant->language = $language;
        $tenant->languages = $languages;

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));
        TenantCollection::invalidateCache();

        return $tenant;
    }
}
