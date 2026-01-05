<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Traits\MenuAttributeTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class MenuFieldTraitTest extends TestCase
{
    public function testShowInMenuField(): void
    {
        $content = TestMenuFieldActiveForm::make()
            ->model(TestMenuEntry::create())
            ->render();

        self::assertStringContainsString('<input type="checkbox" id="entry-show-in-menu" class="input" name="Entry[show_in_menu]" value="1" checked>', $content);
    }
}

class TestMenuFieldActiveForm extends EntryActiveForm
{
    use MenuFieldTrait;

    #[Override]
    public function configure(): void
    {
        $this->rows = [
            $this->getShowInMenuField(),
        ];

        parent::configure();
    }
}

class TestMenuEntry extends TestEntry
{
    use MenuAttributeTrait;

    public bool $show_in_menu = true;
}
