<?php

namespace davidhirtz\yii2\cms\tests\support\fixtures\traits;

use davidhirtz\yii2\cms\tests\support\fixtures\AssetFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\EntryFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\FileFixture;
use davidhirtz\yii2\cms\tests\support\fixtures\SectionFixture;

trait CmsFixturesTrait
{
    public function _fixtures(): array
    {
        $dir = codecept_data_dir();

        return [
            'entries' => [
                'class' => EntryFixture::class,
                'dataFile' => $dir . 'entries.php',
            ],
            'sections' => [
                'class' => SectionFixture::class,
                'dataFile' => $dir . 'sections.php',
            ],
            'files' => [
                'class' => FileFixture::class,
                'dataFile' => $dir . 'files.php',
            ],
            'assets' => [
                'class' => AssetFixture::class,
                'dataFile' => $dir . 'assets.php',
            ],
        ];
    }

}