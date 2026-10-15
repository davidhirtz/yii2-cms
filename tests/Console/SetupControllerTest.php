<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Console;

use Hirtz\Cms\Console\Controllers\SetupController;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\console\ExitCode;

class SetupControllerTest extends TestCase
{
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
    }

    #[Override]
    protected function tearDown(): void
    {
        @unlink($this->file);
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

    public function testTheFileInTheRootConfigIsTheDefault(): void
    {
        $root = Yii::getAlias('@runtime/' . uniqid('setup-root-'));
        $this->file = "$root/config/setup.php";
        mkdir(dirname($this->file), 0777, true);

        Yii::setAlias('@root', $root);
        $this->writeSetupFile("return ['entries' => [['status' => Entry::STATUS_ENABLED, 'name' => 'Root', 'slug' => 'root']]];");

        try {
            self::assertSame(ExitCode::OK, $this->runSetup(['file' => null]));
            self::assertNotNull(Entry::findOne(['name' => 'Root']));
        } finally {
            @unlink($this->file);
            @rmdir(dirname($this->file));
            @rmdir($root);
        }
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

    public function testAMissingFileIsRefused(): void
    {
        self::assertSame(ExitCode::NOINPUT, $this->runSetup());
        self::assertSame(0, (int)Entry::find()->count());
    }

    /**
     * @param array<string, mixed> $params
     */
    private function runSetup(array $params = []): int
    {
        $params = array_filter([...['file' => $this->file], ...$params], fn (mixed $value): bool => $value !== null);
        return $this->createController()->runAction('index', $params);
    }

    private function createController(): SetupControllerMock
    {
        return new SetupControllerMock('setup', Application::current());
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

class SetupControllerMock extends SetupController
{
    use StdOutBufferControllerTrait;
}
