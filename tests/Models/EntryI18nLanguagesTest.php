<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Yii;

class EntryI18nLanguagesTest extends TestCase
{
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

    public function testTheSourceLanguageStaysForATenantWithoutIt(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        $entry = TestEntry::create();
        $entry->populateTenantRelation($this->createTenant('fr', []));

        self::assertSame(['en-US', 'fr'], $entry->getI18nLanguages());
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
