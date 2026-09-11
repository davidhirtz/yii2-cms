<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntryAsset;
use Hirtz\Cms\Test\TestCase;

class AssetTest extends TestCase
{
    use CmsFixtureTrait;

    public function testCreateEntryAsset(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $file = $this->getFileFromFixture('file-5');

        $asset = TestEntryAsset::create();
        $asset->populateModelRelation($entry);
        $asset->populateFileRelation($file);

        self::assertTrue($asset->save(), implode(' ', $asset->getErrorSummary(true)));
        self::assertSame(3, $asset->position);
        self::assertSame(3, $asset->model->asset_count);
        self::assertTrue($asset->isModel(Entry::class));
    }

    public function testCreateSectionAsset(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $file = $this->getFileFromFixture('file-5');

        $asset = SectionAsset::create();
        $asset->populateModelRelation($section);
        $asset->populateFileRelation($file);

        self::assertTrue($asset->save(), implode(' ', $asset->getErrorSummary(true)));
        self::assertSame(5, $asset->position);
        self::assertSame(5, $asset->model->asset_count);
    }

    public function testTheLoadedAssetIsItsSubclass(): void
    {
        self::assertInstanceOf(EntryAsset::class, $this->getAssetFromFixture('entry-asset'));
        self::assertInstanceOf(SectionAsset::class, $this->getAssetFromFixture('section-image-1'));
    }

    public function testCreateAssetWithInvalidAttributes(): void
    {
        $asset = TestEntryAsset::create();

        self::assertFalse($asset->save());
        self::assertArrayHasKey('file_id', $asset->getErrors());
        self::assertArrayHasKey('model_id', $asset->getErrors());

        $asset->model_id = 100;
        $asset->file_id = 100;

        self::assertFalse($asset->save());
        self::assertArrayHasKey('file_id', $asset->getErrors());
    }

    public function testUpdateAsset(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = '<p>Updated content</p>';

        $updatedAt = $asset->model->updated_at;

        self::assertSame(1, $asset->update());
        self::assertSame('<p>Updated content</p>', $asset->content);
        self::assertTrue($asset->model->updated_at > $updatedAt);

        $asset->populateFileRelation($this->getFileFromFixture('file-5'));
        self::assertSame(1, $asset->update());

        self::assertSame(3, $asset->file->asset_count);
        self::assertSame(0, $this->getFileFromFixture('file-1')->asset_count);
    }

    public function testTheModelIsImmutable(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->model_id = $this->getEntryFromFixture('post-1')->id;

        self::assertFalse($asset->update());
        self::assertArrayHasKey('model_id', $asset->getErrors());
    }

    public function testDeleteAsset(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        self::assertSame(1, $asset->delete());

        self::assertSame(0, $this->getFileFromFixture('file-1')->asset_count);
    }
}
