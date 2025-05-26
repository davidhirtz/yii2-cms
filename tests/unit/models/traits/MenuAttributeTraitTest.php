<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\unit\models\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\traits\MenuAttributeTrait;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;

class MenuAttributeTraitTest extends Unit
{
    public function testRules(): void
    {
        $model = TestMenuEntry::create();
        self::assertContains([['show_in_menu'], 'boolean'], $model->rules());
    }

    public function testAttributeLabel(): void
    {
        $model = TestMenuEntry::create();
        self::assertArrayHasKey('show_in_menu', $model->attributeLabels());
    }
}

class TestMenuEntry extends TestEntry
{
    use MenuAttributeTrait;
}
