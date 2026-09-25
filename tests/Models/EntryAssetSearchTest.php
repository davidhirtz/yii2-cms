<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\User;

class EntryAssetSearchTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * An entry asset declares neither `name` nor `content`, so its document carries the alt text alone.
     */
    public function testTheDocumentCarriesTheAltText(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->alt_text = 'A red bicycle';

        $document = $asset->getSearchDocuments()[0];

        self::assertStringContainsString('A red bicycle', $document->content);
    }

    public function testTheResultIsNamedLikeTheHeader(): void
    {
        // The owner sees every hit, so the result is not hidden by the entry permission.
        $this->getWebUser()->setIdentity(User::findOne(['name' => 'owner']));

        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->setAttributes(['name' => 'Cover image'], false);
        $result = $asset->getSearchResult();

        self::assertSame($asset->model->getAdminName(), $result?->title);
        self::assertSame([$asset->getAdminSubtitle()], $result->subtitles);
    }

    public function testAHitIsHiddenWithoutTheEntryPermission(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        self::assertNull($asset->getSearchResult());
    }

    /**
     * The searchable attributes are custom attributes, so the change detection has to see them in the JSON column.
     */
    public function testSavingTheAltTextWritesTheDocuments(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->alt_text = 'A red bicycle';

        self::assertSame(1, $asset->update());

        $documents = Search::find()
            ->where([
                'model_class' => $asset::class,
                'model_id' => $asset->id,
            ])
            ->all();

        self::assertNotEmpty($documents);

        foreach ($documents as $document) {
            self::assertStringContainsString('A red bicycle', $document->content);
        }
    }

    /**
     * The base query is unscoped, so a subclass must not index another subclass's rows.
     */
    public function testTheSearchableQueryIsScopedToTheSubclass(): void
    {
        foreach (EntryAsset::findSearchable()->all() as $asset) {
            self::assertInstanceOf(EntryAsset::class, $asset);
        }
    }
}
