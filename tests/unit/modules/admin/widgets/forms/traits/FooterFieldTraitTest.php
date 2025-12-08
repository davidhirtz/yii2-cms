<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\modules\admin\widgets\forms\traits;

use Codeception\Test\Unit;
use Hirtz\Cms\models\traits\FooterAttributeTrait;
use Hirtz\Cms\modules\admin\widgets\forms\EntryActiveForm;
use Hirtz\Cms\modules\admin\widgets\forms\traits\FooterFieldTrait;
use Hirtz\Cms\tests\data\models\TestEntry;
use Hirtz\Skeleton\codeception\traits\AssetDirectoryTrait;

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

        parent::init();
    }
}

class TestFooterEntry extends TestEntry
{
    use FooterAttributeTrait;

    public bool $show_in_footer = true;
}
