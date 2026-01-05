<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\FooterFieldTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class FooterFieldTraitTest extends TestCase
{
    public function testShowInFooterField(): void
    {
        $content = TestFooterFieldActiveForm::make()
            ->model(TestFooterEntry::create())
            ->render();

        self::assertStringContainsString('<input type="checkbox" id="entry-show-in-footer" class="input" name="Entry[show_in_footer]" value="1" checked>', $content);
    }
}

class TestFooterFieldActiveForm extends EntryActiveForm
{
    use FooterFieldTrait;

    #[Override]
    public function configure(): void
    {
        $this->rows = [
            $this->getShowInFooterField(),
        ];

        parent::configure();
    }
}

class TestFooterEntry extends TestEntry
{
    use FooterAttributeTrait;

    public bool $show_in_footer = true;
}
