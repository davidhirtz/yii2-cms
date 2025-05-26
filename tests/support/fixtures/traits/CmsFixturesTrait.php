<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures\traits;

use davidhirtz\yii2\cms\tests\support\fixtures\AssetFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\CategoryFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\EntryCategoryFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\EntryFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\FileFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\SectionEntryFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\SectionFixture;

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
