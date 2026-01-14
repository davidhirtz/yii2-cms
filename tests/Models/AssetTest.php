<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestAsset;
use Hirtz\Cms\Test\TestCase;


class AssetTest extends TestCase
{
    use CmsFixtureTrait;

    public function testCreateEntryAsset(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $file = $this->getFileFromFixture('file-5');

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
        $section = $this->getSectionFromFixture('section-headline');
        $file = $this->getFileFromFixture('file-5');

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
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = '<p>Updated content</p>';

        $updatedAsset = $asset->entry->updated_at;

        self::assertTrue(!!$asset->update());
        self::assertEquals('<p>Updated content</p>', $asset->content);
        self::assertTrue($asset->entry->updated_at > $updatedAsset);

        $asset->populateFileRelation($this->getFileFromFixture('file-5'));
        self::assertTrue(!!$asset->update());

        self::assertEquals(3, $asset->file->getAttribute('cms_asset_count'));

        $file = $this->getFileFromFixture('file-1');
        self::assertEquals(0, $file->getAttribute('cms_asset_count'));
    }

    public function testUpdateAssetWithInvalidAttributes(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->populateEntryRelation($this->getEntryFromFixture('post-1'));

        self::assertFalse($asset->update());

        self::assertArrayHasKey('entry_id', $asset->getErrors());

        $asset->populateSectionRelation($this->getSectionFromFixture('section-headline'));
        self::assertFalse($asset->update());

        self::assertArrayHasKey('section_id', $asset->getErrors());
    }

    public function testDeleteAsset(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        self::assertTrue($asset->delete() === 1);

        $file = $this->getFileFromFixture('file-1');
        self::assertEquals(0, $file->getAttribute('cms_asset_count'));
    }
}
