<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Test\TestCase;

class EntryRelationTrailTest extends TestCase
{
    public function testTheModelOfADeletedRelationFormatsWithoutItsClass(): void
    {
        $relation = new SectionEntry(['id' => 999_999]);

        self::assertNull($relation->getRelationFromForeignKey('model_id'));
        self::assertSame('26', $relation->formatTrailAttributeValue('model_id', 26));
        self::assertNotNull($relation->getRelationFromForeignKey('entry_id'));
    }
}
