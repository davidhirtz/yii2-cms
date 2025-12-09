<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\Modules\Admin\Widgets\Forms\Traits;

use Codeception\Test\Unit;
use Hirtz\Cms\Models\traits\MenuAttributeTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait;
use Hirtz\Cms\tests\data\models\TestEntry;
use Hirtz\Skeleton\Codeception\traits\AssetDirectoryTrait;

class MenuFieldTraitTest extends Unit
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
        $html = TestMenuFieldActiveForm::widget([
            'model' => TestMenuEntry::create(),
        ]);

        self::assertStringContainsString('show_in_menu', $html);
    }
}

/**
 * @property TestMenuEntry $model
 */
class TestMenuFieldActiveForm extends EntryActiveForm
{
    use MenuFieldTrait;

    #[\Override]
    public function init(): void
    {
        $this->action = '/';
        $this->fields = ['show_in_menu'];
    }
}

class TestMenuEntry extends TestEntry
{
    use MenuAttributeTrait;

    public bool $show_in_menu = true;
}
