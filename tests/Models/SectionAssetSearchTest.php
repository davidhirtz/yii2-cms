<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;

class SectionAssetSearchTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheSearchableQueryLoadsTheOwnersOfTheAssets(): void
    {
        $this->getWebUser()->setIdentity(User::findOne(['name' => 'owner']));

        $ids = [];

        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->createSectionAsset($i);
        }

        $assets = SectionAsset::findSearchable()
            ->andWhere([SectionAsset::tableName() . '.[[id]]' => $ids])
            ->all();

        self::assertCount(3, $assets);

        $count = $this->countQueries(function () use ($assets): void {
            foreach ($assets as $asset) {
                $asset->getSearchResult();
            }
        });

        self::assertSame(0, $count);
    }

    private function createSectionAsset(int $i): int
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = "Entry $i";
        $entry->slug = "section-asset-search-$i";
        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $section = Section::create();
        $section->loadDefaultValues();
        $section->type = Section::TYPE_DEFAULT;
        $section->populateEntryRelation($entry);
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $asset = SectionAsset::create();
        $asset->populateModelRelation($section);
        $asset->file_id = $this->getFileFixtureData('file-1')['id'];
        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));

        return $asset->id;
    }
}
