<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\modules\admin\widgets\forms\traits;

use Codeception\Test\Unit;
use Hirtz\Cms\models\traits\MenuAttributeTrait;
use Hirtz\Cms\modules\admin\widgets\forms\EntryActiveForm;
use Hirtz\Cms\modules\admin\widgets\forms\traits\MenuFieldTrait;
use Hirtz\Cms\tests\data\models\TestEntry;
use Hirtz\Skeleton\codeception\traits\AssetDirectoryTrait;

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

        parent::init();
    }
}

class TestMenuEntry extends TestEntry
{
    use MenuAttributeTrait;

    public bool $show_in_menu = true;
}
