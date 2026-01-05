<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Traits;

use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;

class FooterAttributeTraitTest extends TestCase
{
    public function testRules(): void
    {
        $model = TestFooterEntry::create();
        self::assertContains([['show_in_footer'], 'boolean'], $model->rules());
    }

    public function testAttributeLabel(): void
    {
        $model = TestFooterEntry::create();
        self::assertArrayHasKey('show_in_footer', $model->attributeLabels());
    }
}

class TestFooterEntry extends TestEntry
{
    use FooterAttributeTrait;
}
