<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\Modules\Admin\Widgets\Forms\Traits;

use Codeception\Test\Unit;
use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\FooterFieldTrait;
use Hirtz\Cms\tests\data\Models\TestEntry;
use Hirtz\Skeleton\Codeception\Traits\AssetDirectoryTrait;

class FooterFieldTraitTest extends Unit
{
    use AssetDirectoryTrait;

    protected function _before(): void
    {
        $this->createAssetDirectory();
        parent::_before();
    }

    protected function _after(): void
    {
        $this->removeAssetDirectory();
        parent::_after();
    }

    public function testField(): void
    {
        $html = TestFooterFieldActiveForm::widget([
            'model' => TestFooterEntry::create(),
        ]);

        self::assertStringContainsString('show_in_footer', $html);
    }
}

/**
 * @property TestFooterEntry $model
 */
class TestFooterFieldActiveForm extends EntryActiveForm
{
    use FooterFieldTrait;

    #[\Override]
    public function init(): void
    {
        $this->action = '/';
        $this->fields = ['show_in_footer'];
    }
}

class TestFooterEntry extends TestEntry
{
    use FooterAttributeTrait;

    public bool $show_in_footer = true;
}
