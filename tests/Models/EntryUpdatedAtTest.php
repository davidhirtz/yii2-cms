<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Actions\DeleteSections;
use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Actions\ReorderEntryRelations;
use Hirtz\Cms\Models\Actions\ReorderSections;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestEntryAsset;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Actions\ReorderAssets;
use Hirtz\Skeleton\Db\DateTime;
use Override;

/**
 * The entry's `updated_at` is its sitemap `lastmod`, so whatever changes the entry's page moves it. Each test ages
 * the entry first, so none depends on the clock second.
 */
class EntryUpdatedAtTest extends TestCase
{
    use CmsFixtureTrait;

    private const int ENTRY_ID = 1;

    private int $aged;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableSectionEntries = true;
    }

    public function testUpdatingASectionTouchesTheEntry(): void
    {
        $this->ageEntry();

        $section = $this->getSectionFromFixture('section-column');
        $section->status = TestSection::STATUS_DISABLED;

        self::assertSame(1, $section->update(false));
        $this->assertEntryTouched();
    }

    public function testDeletingASectionTouchesTheEntry(): void
    {
        $this->ageEntry();

        self::assertSame(1, $this->getSectionFromFixture('section-column')->delete());
        $this->assertEntryTouched();
    }

    public function testBatchDeletingSectionsTouchesTheEntry(): void
    {
        $this->ageEntry();

        DeleteSections::create([$this->getSectionFromFixture('section-column')]);
        $this->assertEntryTouched();
    }

    public function testMovingASectionTouchesBothEntries(): void
    {
        $this->ageEntry();
        $aged = $this->aged;
        $this->ageEntry(2);

        $section = $this->getSectionFromFixture('section-column');
        $section->entry_id = 2;

        self::assertSame(1, $section->update(false));
        $this->assertEntryTouched(2);

        $this->aged = $aged;
        $this->assertEntryTouched();
    }

    public function testDuplicatingASectionTouchesTheEntry(): void
    {
        $this->ageEntry();

        DuplicateSection::create([$this->getSectionFromFixture('section-headline')]);
        $this->assertEntryTouched();
    }

    public function testReorderingSectionsTouchesTheEntry(): void
    {
        $this->ageEntry();

        self::assertNotFalse((new ReorderSections(TestEntry::findOne(self::ENTRY_ID), [2, 1]))->run());
        $this->assertEntryTouched();
    }

    public function testAddingAnEntryAssetTouchesTheEntry(): void
    {
        $this->ageEntry();

        $asset = TestEntryAsset::create();
        $asset->populateModelRelation($this->getEntryFromFixture('page-enabled'));
        $asset->populateFileRelation($this->getFileFromFixture('file-5'));

        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));
        $this->assertEntryTouched();
    }

    public function testAddingASectionAssetTouchesTheEntry(): void
    {
        $this->ageEntry();

        $asset = SectionAsset::create();
        $asset->populateModelRelation($this->getSectionFromFixture('section-headline'));
        $asset->populateFileRelation($this->getFileFromFixture('file-1'));

        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));
        $this->assertEntryTouched();
    }

    public function testUpdatingASectionAssetTouchesTheEntry(): void
    {
        $this->ageEntry();

        $asset = SectionAsset::findOne($this->getAssetFixtureData('section-image-1')['id']);
        self::assertNotNull($asset);

        $asset->status = SectionAsset::STATUS_DISABLED;

        self::assertSame(1, $asset->update(false));
        $this->assertEntryTouched();
    }

    public function testDeletingASectionAssetTouchesTheEntry(): void
    {
        $this->ageEntry();

        $asset = SectionAsset::findOne($this->getAssetFixtureData('section-image-1')['id']);
        self::assertNotNull($asset);

        self::assertSame(1, $asset->delete());
        $this->assertEntryTouched();
    }

    public function testReorderingSectionAssetsTouchesTheEntry(): void
    {
        $this->ageEntry();

        $action = new ReorderAssets($this->getSectionFromFixture('section-headline'), [7, 6, 5, 4]);

        self::assertNotFalse($action->run());
        $this->assertEntryTouched();
    }

    public function testLinkingAnEntryToASectionTouchesTheSectionsEntry(): void
    {
        $this->ageEntry();

        $relation = SectionEntry::create();
        $relation->populateModelRelation($this->getSectionFromFixture('section-blog-draft'));
        $relation->populateEntryRelation(TestEntry::findOne(4));

        self::assertTrue($relation->insert(), print_r($relation->getErrors(), true));
        $this->assertEntryTouched();
    }

    public function testUnlinkingAnEntryFromASectionTouchesTheSectionsEntry(): void
    {
        $this->ageEntry();

        $relation = SectionEntry::findOne(2);
        self::assertNotNull($relation);

        self::assertSame(1, $relation->delete());
        $this->assertEntryTouched();
    }

    public function testReorderingTheLinkedEntriesTouchesTheSectionsEntry(): void
    {
        $this->ageEntry();

        $action = new ReorderEntryRelations($this->getSectionFromFixture('section-blog-draft'), [1, 2]);

        self::assertNotFalse($action->run());
        $this->assertEntryTouched();
    }

    public function testAddingACategoryTouchesTheEntry(): void
    {
        $this->ageEntry(2);

        $junction = EntryCategory::create();
        $junction->populateEntryRelation(TestEntry::findOne(2));
        $junction->populateCategoryRelation($this->getCategoryFromFixture('root-2'));

        self::assertTrue($junction->insert(), print_r($junction->getErrors(), true));
        $this->assertEntryTouched(2);
    }

    public function testRemovingACategoryTouchesTheEntry(): void
    {
        $this->ageEntry();

        $junction = EntryCategory::findOne(['entry_id' => self::ENTRY_ID, 'category_id' => 1]);
        self::assertNotNull($junction);

        self::assertSame(1, $junction->delete());
        $this->assertEntryTouched();
    }

    private function ageEntry(int $id = self::ENTRY_ID): void
    {
        $aged = new DateTime('-1 hour');
        TestEntry::updateAll(['updated_at' => $aged], ['id' => $id]);

        $this->aged = $aged->getTimestamp();
    }

    private function assertEntryTouched(int $id = self::ENTRY_ID): void
    {
        $updatedAt = TestEntry::findOne($id)?->updated_at;

        self::assertNotNull($updatedAt);
        self::assertGreaterThan($this->aged, $updatedAt->getTimestamp(), "Entry $id was not touched.");
    }
}
