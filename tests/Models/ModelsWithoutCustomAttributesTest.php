<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Widgets\MetaTags;
use Override;
use Stringable;
use Yii;
use yii\db\Query;

/**
 * A project may filter the default custom attributes of a section, a category or an asset down to the ones it
 * uses, so every one of them is optional: nothing reading the record may assume one is declared.
 */
class ModelsWithoutCustomAttributesTest extends TestCase
{
    use CmsFixtureTrait;

    public function testASectionWithoutASlugSaves(): void
    {
        $section = $this->createSection();
        $section->status = Section::STATUS_DISABLED;

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));
    }

    public function testASectionWithoutASlugIsIdentifiedByItsId(): void
    {
        $section = $this->createSection();

        self::assertSame("section-$section->id", $section->getHtmlId());
    }

    public function testTheGridShowsASectionWithoutNameOrContentAsUntitled(): void
    {
        $section = $this->createSection();
        $section->populateRelation('assets', []);

        $html = (string)SectionWithoutCustomAttributesGridView::make()->getNameColumnContent($section);

        self::assertStringContainsString(Yii::t('cms', 'COMMON_NO_TITLE'), $html);
    }

    public function testAnAssetWithoutCustomAttributesRendersAsAnArtwork(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        $bare = new EntryAssetWithoutCustomAttributes();
        EntryAsset::populateRecord($bare, $asset->getOldAttributes());
        $bare->populateRelation('file', $asset->file);

        $html = (string)Artwork::make()
            ->asset($bare)
            ->adminLink(false);

        self::assertStringContainsString('<img', $html);
        self::assertStringNotContainsString('<figcaption', $html);
    }

    public function testTheMetaTagsOfACategoryWithoutTitleFallBackToItsName(): void
    {
        $category = $this->getCategoryFromFixture('root-1');

        $bare = new CategoryWithoutCustomAttributes();
        Category::populateRecord($bare, $category->getOldAttributes());

        Yii::$app->set('view', Yii::$app->getComponents()['view']);
        MetaTags::make()->model($bare)->__toString();

        self::assertSame('Root category 1', Yii::$app->getView()->title);
    }

    private function createSection(): SectionWithoutCustomAttributes
    {
        $section = $this->getSectionFromFixture('section-headline');

        // the raw row, since `populateRecord()` decodes the JSON column itself
        $row = (new Query())->from(Section::tableName())->where(['id' => $section->id])->one();
        self::assertIsArray($row);

        $bare = new SectionWithoutCustomAttributes();
        Section::populateRecord($bare, $row);
        $bare->populateRelation('entry', $section->entry);

        return $bare;
    }
}

class SectionWithoutCustomAttributes extends Section
{
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        return [];
    }
}

class CategoryWithoutCustomAttributes extends Category
{
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        return [];
    }
}

class EntryAssetWithoutCustomAttributes extends EntryAsset
{
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        return [];
    }
}

class SectionWithoutCustomAttributesGridView extends SectionGridView
{
    #[Override]
    public function getNameColumnContent(Section $section): Stringable|string
    {
        return parent::getNameColumnContent($section);
    }
}
