<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileAssetGridView;
use Override;
use Yii;
use yii\web\Controller;

/**
 * @see https://github.com/davidhirtz/yii2-monorepo/issues/565
 */
class FileAssetGridViewTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->controller = new Controller('test', Yii::$app);
    }

    public function testASectionIsNamedByItsEntryAndPosition(): void
    {
        $file = $this->getFileFromFixture('file-3');

        $html = (string)FileAssetGridView::make()
            ->file($file);

        $entry = $this->getEntryFromFixture('page-enabled');

        self::assertStringContainsString(
            '<span class="model-heading"><span class="model-heading-title">' . $entry->getAdminName() . '</span>'
            . '<span class="model-heading-subtitle">',
            $html,
        );
    }
}
