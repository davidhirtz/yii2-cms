<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\CustomAttributes\FileCustomAttribute;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\FileReference;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

/**
 * Every cascade an owner can be removed by goes through its model, so its file references go with it.
 */
class FileReferenceTest extends TestCase
{
    use CmsFixtureTrait;

    private File $file;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Block::getModule()->enableBlocks = true;

        foreach ([TestEntry::class, Section::class, Block::class, EntryAsset::class] as $class) {
            Yii::$container->set($class, [
                'customAttributes' => [FileCustomAttribute::make('hero')],
            ]);
        }

        $this->file = $this->getFileFromFixture('file-1');
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ([TestEntry::class, Section::class, Block::class, EntryAsset::class] as $class) {
            Yii::$container->clear($class);
        }

        parent::tearDown();
    }

    public function testAnEntryTakesTheReferencesOfItsSectionsAndAssetsWithIt(): void
    {
        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = 'Å Москва';
        $entry->slug = 'a-moskva';
        $entry->setAttribute('hero', $this->file->id);
        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $section = Section::create();
        $section->loadDefaultValues();
        $section->populateEntryRelation($entry);
        $section->setAttribute('hero', $this->file->id);
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $asset = EntryAsset::create();
        $asset->loadDefaultValues();
        $asset->populateModelRelation($entry);
        $asset->file_id = $this->getFileFromFixture('file-2')->id;
        $asset->setAttribute('hero', $this->file->id);
        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));

        self::assertSame(3, $this->countReferences($entry, $section, $asset));
        self::assertSame(3, $this->refreshFile()->reference_count);

        $entry = TestEntry::findOne($entry->id) ?? self::fail('The entry was not found.');
        self::assertSame(1, $entry->delete());

        self::assertSame(0, $this->countReferences($entry, $section, $asset));
        self::assertSame(0, $this->refreshFile()->reference_count);
    }

    public function testABlockTakesItsReferencesWithIt(): void
    {
        $block = Block::create();
        $block->loadDefaultValues();
        $block->name = '東京';
        $block->setAttribute('hero', $this->file->id);
        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        self::assertSame(1, $this->countReferences($block));

        self::assertSame(1, $block->delete());
        self::assertSame(0, $this->countReferences($block));
    }

    public function testATenantWithEntriesIsNotDeleted(): void
    {
        $tenant = Tenant::findOne($this->getEntryFromFixture('page-enabled')->tenant_id);

        self::assertNotNull($tenant);
        self::assertFalse($tenant->delete());
    }

    private function countReferences(ActiveRecord ...$owners): int
    {
        $count = 0;

        foreach ($owners as $owner) {
            $count += (int)FileReference::find()
                ->where(['model_class' => $owner::class, 'model_id' => $owner->getPrimaryKey()])
                ->count();
        }

        return $count;
    }

    private function refreshFile(): File
    {
        return File::findOne($this->file->id) ?? self::fail('The file was not found.');
    }
}
