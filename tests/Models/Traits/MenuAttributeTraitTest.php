<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Traits;

use Hirtz\Cms\Models\Traits\MenuAttributeTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class MenuAttributeTraitTest extends TestCase
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

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getMenuAttributeRules(),
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->getMenuAttributeLabels(),
        ];
    }
}
