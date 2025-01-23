<?php

namespace davidhirtz\yii2\cms\tests\unit\modules\admin\widgets\forms\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\traits\FooterAttributeTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\FooterFieldTrait;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;

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

    public function init(): void
    {
        $this->action = '/';
        $this->fields = ['show_in_footer'];

        parent::init();
    }
}

class TestFooterEntry extends TestEntry
{
    use FooterAttributeTrait;

    public bool $show_in_footer = true;
}
