<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\models\traits;

use Codeception\Test\Unit;
use Hirtz\Cms\models\traits\FooterAttributeTrait;
use Hirtz\Cms\tests\data\models\TestEntry;

class FooterAttributeTraitTest extends Unit
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
