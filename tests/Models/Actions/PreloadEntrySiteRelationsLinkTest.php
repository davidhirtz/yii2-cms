<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Override;
use Yii;

/**
 * The targets of links share the batch the related entries load in, so a page pays nothing for its links.
 */
class PreloadEntrySiteRelationsLinkTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::getModule()->enableNestedEntries = true;
        self::getModule()->enableSectionEntries = true;

        Yii::$container->set(Section::class, PreloadLinkSection::class);
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);

        // A request has its tenants and folders long before it renders.
        TenantCollection::getAll();
        FolderCollection::getAll();
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        EntryLinks::reset();
        ActiveQuery::resetStatus();

        parent::tearDown();
    }

    /**
     * The first target joins the batch the ancestors and listed entries load in, or opens it: the entries and their
     * permalinks. Every further one is free.
     */
    public function testLinksInSectionsCostNoQueryOfTheirOwn(): void
    {
        $target = $this->createTarget();
        $without = $this->countPreloadQueries();

        $this->updateSection("<p><a href=\"entry:$target->id\">Target</a></p>");
        $one = $this->countPreloadQueries();
        self::assertLessThanOrEqual($without + 2, $one);

        $this->updateSection("<p><a href=\"entry:$target->id\">Target</a> <a href=\"entry:5\">Post</a> <a href=\"entry:2\">Draft</a> <a href=\"entry:999999\">Gone</a></p>", 'entry:4#top');
        self::assertSame($one, $this->countPreloadQueries());
    }

    public function testRenderingThePreloadedLinksRunsNoQuery(): void
    {
        $target = $this->createTarget();
        $this->updateSection("<p><a href=\"entry:$target->id\">Read more</a> <a href=\"entry:2\">Draft</a> <a href=\"entry:999999\">Gone</a></p>", "entry:$target->id#top");

        $entry = $this->getEntryFromFixture('page-enabled');
        new PreloadEntrySiteRelations(['entry' => $entry]);

        $section = $entry->sections[2] ?? self::fail('The section did not load.');
        $content = null;
        $link = null;

        $count = $this->countQueries(function () use ($section, &$content, &$link): void {
            $content = $section->getVisibleAttribute('content');
            $link = $section->getVisibleAttribute('link');
        });

        self::assertSame(0, $count);
        self::assertSame('<p><a href="https://www.domain.localhost/target" aria-label="Read more: Target">Read more</a> Draft Gone</p>', $content);
        self::assertSame('https://www.domain.localhost/target#top', $link);
    }

    /**
     * Assets load after the entries, so their links cost one batch: the entries and their permalinks.
     */
    public function testLinksInAssetsCostOneBatch(): void
    {
        $without = $this->countPreloadQueries();

        $asset = Asset::find()->where(['model_class' => Section::class])->one();
        self::assertNotNull($asset);
        $asset->content = '<p><a href="entry:5">Ågot 東京 🚀</a></p>';
        $asset->link = 'entry:4';
        $asset->updateAttributes(['custom_attributes' => $asset->getSerializedCustomAttributes()]);

        self::assertSame($without + 2, $this->countPreloadQueries());
    }

    private function createTarget(): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = 'Target';
        $entry->slug = 'target';
        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        $section = TestSection::instantiateByType(TestSection::TYPE_TEXT_COLUMN);
        $section->populateEntryRelation($entry);
        $section->content = '<p>Text</p>';
        self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));

        return $entry;
    }

    private function updateSection(string $content, ?string $link = null): void
    {
        $section = PreloadLinkSection::findOne(2);
        self::assertNotNull($section);
        $section->content = $content;
        $section->link = $link;
        $section->updateAttributes(['custom_attributes' => $section->getSerializedCustomAttributes()]);
    }

    private function countPreloadQueries(): int
    {
        EntryLinks::reset();
        $entry = $this->getEntryFromFixture('page-enabled');

        return $this->countQueries(fn () => new PreloadEntrySiteRelations(['entry' => $entry]));
    }
}

/**
 * @property string|null $link
 */
class PreloadLinkSection extends TestSection
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            LinkCustomAttribute::make('link'),
        ];
    }
}
