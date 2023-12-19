<?php

namespace davidhirtz\yii2\cms\tests\unit;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\Entry;

class EntryTest extends Unit
{
    public function testCreateEntry()
    {
        $entry = Entry::create();
        $entry->name = 'Test Entry';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        $this->assertTrue($entry->save());
        $this->assertTrue($entry->isIndex());
    }
}
