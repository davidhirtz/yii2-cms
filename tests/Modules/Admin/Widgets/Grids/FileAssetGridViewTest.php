<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Actions\SyncFileReferences;
use Hirtz\Media\Models\CustomAttributes\FileCustomAttribute;
use Hirtz\Media\Models\File;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileAssetGridView;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileReferenceGridView;
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

    /**
     * The owners are loaded with what their heading reads: the sections share one query for their entries.
     */
    public function testTheHeadingsOfManyRowsAreLoadedInFewQueries(): void
    {
        $file = $this->getFileFromFixture('file-1');

        foreach (['section-headline', 'section-column', 'section-gallery', 'section-entry-draft'] as $key) {
            $asset = SectionAsset::create();
            $asset->loadDefaultValues();
            $asset->populateModelRelation($this->getSectionFromFixture($key));
            $asset->file_id = $file->id;
            self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));
        }

        $file = File::findOne($file->id) ?? self::fail('The file was not found.');
        $html = '';

        $queries = $this->countQueries(function () use ($file, &$html): void {
            $html = (string)FileAssetGridView::make()->file($file);
        }, '/^SELECT .* FROM `(entry|section)`/');

        self::assertSame(5, substr_count($html, 'class="model-heading-title"'));
        self::assertSame(3, $queries, 'the entries the file is attached to, the sections, their entries');
    }

    public function testTheHeadingsOfManyReferencesAreLoadedInFewQueries(): void
    {
        Yii::$container->set(Section::class, ['customAttributes' => [FileCustomAttribute::make('hero')]]);
        $file = $this->getFileFromFixture('file-1');

        foreach (['section-headline', 'section-column', 'section-gallery', 'section-entry-draft'] as $key) {
            $section = Section::findOne($this->getSectionFixtureData($key)['id']) ?? self::fail('The section was not found.');
            $section->updateAttributes(['custom_attributes' => [...(array)$section->getAttribute('custom_attributes'), 'hero' => $file->id]]);
            $section->refresh();
            SyncFileReferences::create($section);
        }

        $html = '';

        $queries = $this->countQueries(function () use ($file, &$html): void {
            $html = (string)FileReferenceGridView::make()->file($file);
        }, '/^SELECT .* FROM `(entry|section)`/');

        Yii::$container->clear(Section::class);

        self::assertSame(4, substr_count($html, 'class="model-heading-title"'));
        self::assertSame(2, $queries, 'the sections, their entries');
    }
}
