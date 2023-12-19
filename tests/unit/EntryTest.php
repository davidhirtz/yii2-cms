<?php

namespace davidhirtz\yii2\cms\tests\unit;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\Entry;

class EntryTest extends Unit
{
    //    public function _fixtures(): array
    //    {
    //        return [
    //            'files' => [
    //                'class' => FileFixture::class,
    //                'dataFile' => codecept_data_dir() . 'file.php'
    //            ],
    //        ];
    //    }

    public function testCreateEntry()
    {
        $entry = Entry::create();
        $entry->title = 'Test Entry';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        $this->assertTrue($entry->save());
        $this->assertTrue($entry->isIndex());
    }
}
