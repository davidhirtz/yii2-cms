<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Console\Controllers\EntryController;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Override;
use Yii;

/**
 * `entry_reference` answers who links to an entry; the save keeps it current, `entry/references` rebuilds it.
 */
class EntryReferenceTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$container->set(Section::class, ReferenceSection::class);
        Section::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Section::instance(true);

        parent::tearDown();
    }

    public function testASaveIndexesTheLinksOfEveryAttribute(): void
    {
        $section = $this->createSection(
            '<p><a href="entry:4">Å</a> <a href="entry:4#x">東京</a> <a href="entry:5">🚀</a> <a href="entry:999999">Gone</a></p>',
            'entry:4',
            [['link' => 'entry:5']],
        );

        $references = $this->findReferences($section);

        self::assertSame([4, 5], array_keys($references));
        self::assertSame(3, $references[4]->use_count);
        self::assertSame(['content' => 2, 'link' => 1], $references[4]->attribute_counts);
        self::assertSame(['content' => 1, 'links.link' => 1], $references[5]->attribute_counts);

        self::assertSame(3, Entry::findOne(4)?->reference_count);
        self::assertSame(2, Entry::findOne(5)?->reference_count);
    }

    public function testAChangeUpdatesAndARemovedLinkDeletesItsRow(): void
    {
        $section = $this->createSection('<p><a href="entry:4">A</a></p>', 'entry:5');

        $section->content = '<p><a href="entry:4">A</a> <a href="entry:4">B</a></p>';
        $section->link = null;
        self::assertTrue($section->save(), implode(' ', $section->getErrorSummary(true)));

        $references = $this->findReferences($section);

        self::assertSame([4], array_keys($references));
        self::assertSame(2, $references[4]->use_count);
        self::assertSame(0, Entry::findOne(5)?->reference_count);
    }

    public function testDeletingTheLinkingRecordDeletesItsRows(): void
    {
        $section = $this->createSection('<p><a href="entry:4">A</a></p>');
        self::assertSame(1, $section->delete());

        self::assertSame([], $this->findReferences($section));
        self::assertSame(0, Entry::findOne(4)?->reference_count);
    }

    /**
     * The stored link stays: it renders as its text until the target is back or the editor removes it.
     */
    public function testDeletingTheTargetLeavesTheLinkingRecordAlone(): void
    {
        $section = $this->createSection('<p><a href="entry:5">A</a></p>');
        self::assertSame(1, Entry::findOne(5)?->delete());

        self::assertSame([], $this->findReferences($section));
        self::assertSame('<p><a href="entry:5">A</a></p>', ReferenceSection::findOne($section->id)?->content);
    }

    public function testTheCommandRebuildsWhatAWriteOutsideSaveLeft(): void
    {
        $section = $this->createSection('<p>Text</p>');
        $section->content = '<p><a href="entry:4">A</a></p>';
        $section->updateAttributes(['custom_attributes' => $section->getSerializedCustomAttributes()]);

        $orphan = EntryReference::create();
        $orphan->setAttributes(['entry_id' => 5, 'model_class' => ReferenceSection::class, 'model_id' => 999999, 'use_count' => 1], false);
        self::assertTrue($orphan->insert(false));

        self::assertSame([], $this->findReferences($section));

        $controller = new TestEntryController('entry', Yii::$app);
        $controller->interactive = false;
        $controller->runAction('references');

        self::assertSame([4], array_keys($this->findReferences($section)));
        self::assertNull(EntryReference::findOne($orphan->id));
        self::assertSame(1, Entry::findOne(4)?->reference_count);
    }

    /**
     * @param list<array<string, string>>|null $links
     */
    private function createSection(string $content, ?string $link = null, ?array $links = null): ReferenceSection
    {
        $section = ReferenceSection::instantiateByType(TestSection::TYPE_TEXT_COLUMN);
        $section->populateEntryRelation($this->getEntryFromFixture('page-enabled'));
        $section->content = $content;
        $section->link = $link;
        $section->links = $links;

        self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));

        return $section;
    }

    /**
     * @return array<int, EntryReference>
     */
    private function findReferences(Section $section): array
    {
        return EntryReference::find()
            ->where(['model_class' => $section::class, 'model_id' => $section->id])
            ->orderBy(['entry_id' => SORT_ASC])
            ->indexBy('entry_id')
            ->all();
    }
}

/**
 * @property string|null $link
 * @property list<array<string, mixed>>|null $links
 */
class ReferenceSection extends TestSection
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            LinkCustomAttribute::make('link'),
            GroupCustomAttribute::make('links')
                ->multiple()
                ->attributes([LinkCustomAttribute::make('link')]),
        ];
    }
}

class TestEntryController extends EntryController
{
    use StdOutBufferControllerTrait;
}
