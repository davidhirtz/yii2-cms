<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures\traits;

use Hirtz\Cms\tests\support\fixtures\AssetFixture;
use Hirtz\Cms\tests\support\fixtures\CategoryFixture;
use Hirtz\Cms\tests\support\fixtures\EntryCategoryFixture;
use Hirtz\Cms\tests\support\fixtures\EntryFixture;
use Hirtz\Cms\tests\support\fixtures\FileFixture;
use Hirtz\Cms\tests\support\fixtures\SectionEntryFixture;
use Hirtz\Cms\tests\support\fixtures\SectionFixture;

trait CmsFixturesTrait
{
    public function _fixtures(): array
    {
        $dir = codecept_data_dir();

        return [
            'assets' => [
                'class' => AssetFixture::class,
                'dataFile' => $dir . 'assets.php',
            ],
            'categories' => [
                'class' => CategoryFixture::class,
                'dataFile' => $dir . 'categories.php',
            ],
            'entries' => [
                'class' => EntryFixture::class,
                'dataFile' => $dir . 'entries.php',
            ],
            'entries_categories' => [
                'class' => EntryCategoryFixture::class,
                'dataFile' => $dir . 'entries_categories.php',
            ],
            'files' => [
                'class' => FileFixture::class,
                'dataFile' => $dir . 'files.php',
            ],
            'sections' => [
                'class' => SectionFixture::class,
                'dataFile' => $dir . 'sections.php',
            ],
            'sections_entries' => [
                'class' => SectionEntryFixture::class,
                'dataFile' => $dir . 'sections_entries.php',
            ],
        ];
    }
}
