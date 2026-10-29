<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Setup;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Media\Models\File;
use Hirtz\Media\Test\Traits\MediaFileTrait;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\SetupController;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\console\ExitCode;

class SetupTest extends TestCase
{
    use MediaFileTrait;
    use UserFixtureTrait;

    protected string $applicationClass = Application::class;

    private string $file;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = Entry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;

        // `Entry::validateParentId()` drops the parent while nesting is off, so a sub-entry would become a root one
        $module->enableNestedEntries = true;

        $this->file = Yii::getAlias('@runtime/' . uniqid('setup-') . '.php');
        Yii::setAlias('@root', Yii::getAlias('@runtime/' . uniqid('setup-root-')));
    }

    #[Override]
    protected function tearDown(): void
    {
        @unlink($this->file);
        FileHelper::removeDirectory((string)File::getModule()->uploadPath);
        FileHelper::removeDirectory(Upload::getComponent()->tempPath);

        parent::tearDown();
    }

    public function testTheContentIsInsertedWithItsSectionsCategoriesAndChildren(): void
    {
        $this->writeSetupFile(<<<'PHP'
            return [
                'categories' => [
                    ['status' => Category::STATUS_ENABLED, 'name' => 'News', 'slug' => 'news'],
                ],
                'entries' => fn (): array => [
                    [
                        'status' => Entry::STATUS_ENABLED,
                        'name' => 'Home',
                        'slug' => 'home',
                        'categories' => [['category_id' => Category::findOne(['slug' => 'news'])?->id]],
                        'sections' => [['status' => Section::STATUS_ENABLED, 'name' => 'Intro']],
                        'entries' => [['status' => Entry::STATUS_ENABLED, 'name' => 'About', 'slug' => 'about']],
                    ],
                ],
            ];
            PHP);

        self::assertSame(ExitCode::OK, $this->runSetup());

        $entry = Entry::findOne(['name' => 'Home']);
        $category = Category::findOne(['slug' => 'news']);

        self::assertNotNull($entry);
        self::assertNotNull($category);
        self::assertSame(1, (int)Section::find()->where(['entry_id' => $entry->id])->count());
        self::assertNotNull(EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $category->id]));
        self::assertSame($entry->id, Entry::findOne(['name' => 'About'])?->parent_id);
        self::assertFalse(FolderCollection::getDefault()->getIsNewRecord());
    }

    public function testTheRecordsAreAttributedToTheOwner(): void
    {
        self::assertSame(ExitCode::OK, $this->runSetup(['file' => null]));

        $entry = Entry::find()->one();

        self::assertNotNull($entry);
        self::assertSame($this->getUserFromFixture('owner')->id, $entry->updated_by_user_id);
    }

    public function testWithoutAFileAHomepageIsInserted(): void
    {
        self::assertSame(ExitCode::OK, $this->runSetup(['file' => null]));

        $entry = Entry::find()->one();

        self::assertSame(Entry::getModule()->entryIndexSlug, $entry?->slug);
        self::assertSame(Yii::$app->name, $entry->title);
    }

    public function testASecondRunAddsNothing(): void
    {
        $this->runSetup(['file' => null]);
        $count = (int)Entry::find()->count();

        $this->runSetup(['file' => null]);

        self::assertSame($count, (int)Entry::find()->count());
    }

    public function testAnEntryThatCannotBeInsertedIsReported(): void
    {
        $this->writeSetupFile("return ['entries' => [['name' => '']]];");

        $controller = $this->createController();

        self::assertSame(ExitCode::DATAERR, $controller->runAction('index', ['file' => $this->file]));
        self::assertStringContainsString('Failed to insert', $controller->flushStdOutBuffer());
        self::assertSame(0, (int)Entry::find()->count());
    }

    public function testAFileThatCannotBeReadCostsOnlyItsAsset(): void
    {
        $source = Upload::getComponent()->tempPath . '/Grüße Å 東京 🎉.jpg';
        FileHelper::createDirectory(dirname($source));
        $this->writeImage($source, 60, 40);

        $this->writeSetupFile(<<<PHP
            return ['entries' => [
                [
                    'status' => Entry::STATUS_ENABLED,
                    'name' => 'Home',
                    'slug' => 'home',
                    'assets' => [['path' => '$source', 'alt_text' => 'Grüße 🎉']],
                    'sections' => [['name' => 'Intro', 'assets' => [['url' => 'http://127.0.0.1/gone.jpg']]]],
                ],
            ]];
            PHP);

        $controller = $this->createController();

        self::assertSame(ExitCode::DATAERR, $controller->runAction('index', ['file' => $this->file]));
        self::assertStringContainsString('http://127.0.0.1/gone.jpg could not be read.', $controller->flushStdOutBuffer());

        $entry = Entry::findOne(['name' => 'Home']) ?? self::fail('No entry');
        $section = Section::findOne(['entry_id' => $entry->id]) ?? self::fail('No section');

        self::assertSame(1, $entry->asset_count);
        self::assertSame('Grüße 🎉', $entry->assets[0]->getAttribute('alt_text') ?? null);
        self::assertSame(60, $entry->assets[0]->file?->width);
        self::assertSame(0, $section->asset_count);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function runSetup(array $params = []): int
    {
        $params = array_filter([...['file' => $this->file], ...$params], fn (mixed $value): bool => $value !== null);
        return $this->createController()->runAction('index', $params);
    }

    private function createController(): SetupTestControllerMock
    {
        return new SetupTestControllerMock('setup', Application::current());
    }

    private function writeSetupFile(string $code): void
    {
        file_put_contents($this->file, <<<PHP
            <?php

            use Hirtz\\Cms\\Models\\Category;
            use Hirtz\\Cms\\Models\\Entry;
            use Hirtz\\Cms\\Models\\Section;

            $code
            PHP);
    }
}

class SetupTestControllerMock extends SetupController
{
    use StdOutBufferControllerTrait;
}
