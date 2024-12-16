<?php

namespace davidhirtz\yii2\cms\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\tests\data\models\TestAsset;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use davidhirtz\yii2\cms\tests\support\UnitTester;
use function PHPUnit\Framework\assertTrue;

class AssetTest extends Unit
{
    use CmsFixturesTrait;

    protected UnitTester $tester;

    public function testCreateEntryAsset(): void
    {
        $entry = $this->tester->grabEntryFixture('page-enabled');
        $file = $this->tester->grabFileFixture('file-5');

        $asset = TestAsset::create();
        $asset->populateParentRelation($entry);
        $asset->populateFileRelation($file);

        self::assertTrue($asset->save());
        self::assertEquals(3, $asset->position);
        self::assertEquals(3, $asset->entry->asset_count);
        self::assertNull($asset->section_id);
        self::assertTrue($asset->isEntryAsset());
    }

    public function testCreateSectionAsset(): void
    {
        $section = $this->tester->grabSectionFixture('section-headline');
        $file = $this->tester->grabFileFixture('file-5');

        $asset = TestAsset::create();
        $asset->populateParentRelation($section);
        $asset->populateFileRelation($file);

        self::assertTrue($asset->save());
        self::assertEquals(5, $asset->position);
        self::assertEquals(5, $asset->section->asset_count);
        self::assertEquals(2, $asset->entry->asset_count);
        self::assertEquals($section->entry_id, $asset->entry_id);
        self::assertTrue($asset->isSectionAsset());
    }

    public function testCreateAssetWithInvalidAttributes(): void
    {
        $asset = TestAsset::create();

        self::assertFalse($asset->save());

        self::assertArrayHasKey('entry_id', $asset->getErrors());
        self::assertArrayHasKey('file_id', $asset->getErrors());

        $asset->entry_id = 100;
        $asset->file_id = 100;

        self::assertFalse($asset->save());

        self::assertArrayHasKey('entry_id', $asset->getErrors());
        self::assertArrayHasKey('file_id', $asset->getErrors());

        $asset->section_id = 100;

        self::assertFalse($asset->save());

        self::assertArrayHasKey('entry_id', $asset->getErrors());
    }

    public function testUpdateAsset(): void
    {
        $asset = $this->tester->grabAssetFixture('entry-asset');
        $asset->content = '<p>Updated content</p>';

        $updatedAsset = $asset->entry->updated_at;

        self::assertTrue(!!$asset->update());
        self::assertEquals('<p>Updated content</p>', $asset->content);
        self::assertTrue($asset->entry->updated_at > $updatedAsset);

        $asset->populateFileRelation($this->tester->grabFileFixture('file-5'));
        self::assertTrue(!!$asset->update());

        self::assertEquals(3, $asset->file->getAttribute('cms_asset_count'));

        $file = $this->tester->grabFileFixture('file-1');
        self::assertEquals(0, $file->getAttribute('cms_asset_count'));
    }

    public function testUpdateAssetWithInvalidAttributes(): void
    {
        $asset = $this->tester->grabAssetFixture('entry-asset');
        $asset->populateEntryRelation($this->tester->grabEntryFixture('post-1'));

        self::assertFalse($asset->update());

        self::assertArrayHasKey('entry_id', $asset->getErrors());

        $asset->populateSectionRelation($this->tester->grabSectionFixture('section-headline'));
        self::assertFalse($asset->update());

        self::assertArrayHasKey('section_id', $asset->getErrors());
    }

    public function testDeleteAsset(): void
    {
        $asset = $this->tester->grabAssetFixture('entry-asset');
        assertTrue(!!$asset->delete());

        $file = $this->tester->grabFileFixture('file-1');
        self::assertEquals(0, $file->getAttribute('cms_asset_count'));
    }
}
