<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures\Traits;

use Hirtz\Cms\Test\Fixtures\AssetFixture;
use Hirtz\Cms\Test\Fixtures\CategoryFixture;
use Hirtz\Cms\Test\Fixtures\EntryCategoryFixture;
use Hirtz\Cms\Test\Fixtures\EntryFixture;
use Hirtz\Cms\Test\Fixtures\SectionEntryFixture;
use Hirtz\Cms\Test\Fixtures\SectionFixture;
use Hirtz\Cms\Test\Models\TestAsset;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Media\Models\File;
use Hirtz\Media\Test\Fixtures\FileFixture;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;

trait FixtureTrait
{
    #[Override]
    public function fixtures(): array
    {
        return [
            'asset' => AssetFixture::class,
            'category' => CategoryFixture::class,
            'entry' => EntryFixture::class,
            'entry_category' => EntryCategoryFixture::class,
            'file' => FileFixture::class,
            'section' => SectionFixture::class,
            'section_entry' => SectionEntryFixture::class,
            'user' => UserFixture::class,
        ];
    }

    protected function getAssetFixture(): AssetFixture
    {
        /** @var AssetFixture $fixture */
        $fixture = $this->getFixture('asset');
        return $fixture;
    }

    protected function getAssetFixtureData(string $key): array
    {
        return $this->getAssetFixture()->data[$key];
    }

    protected function getAssetFromFixture(string $key): TestAsset
    {
        return TestAsset::findOne($this->getAssetFixtureData($key)['id']);
    }

    protected function getEntryFixture(): EntryFixture
    {
        /** @var EntryFixture $fixture */
        $fixture = $this->getFixture('entry');
        return $fixture;
    }

    protected function getEntryFixtureData(string $key): array
    {
        return $this->getEntryFixture()->data[$key];
    }

    protected function getEntryFromFixture(string $key): TestEntry
    {
        return TestEntry::findOne($this->getEntryFixtureData($key)['id']);
    }

    protected function getFileFixture(): FileFixture
    {
        /** @var FileFixture $fixture */
        $fixture = $this->getFixture('file');
        return $fixture;
    }

    protected function getFileFixtureData(string $key): array
    {
        return $this->getFileFixture()->data[$key];
    }

    protected function getFileFromFixture(string $key): File
    {
        return File::findOne($this->getFileFixtureData($key)['id']);
    }

    protected function getSectionFixture(): SectionFixture
    {
        /** @var SectionFixture $fixture */
        $fixture = $this->getFixture('section');
        return $fixture;
    }

    protected function getSectionFixtureData(string $key): array
    {
        return $this->getSectionFixture()->data[$key];
    }

    protected function getSectionFromFixture(string $key): TestSection
    {
        return TestSection::findOne($this->getSectionFixtureData($key)['id']);
    }
}
