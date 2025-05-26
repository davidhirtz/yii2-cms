<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\unit\modules\admin\widgets\forms\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\traits\MenuAttributeTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\MenuFieldTrait;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;

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
