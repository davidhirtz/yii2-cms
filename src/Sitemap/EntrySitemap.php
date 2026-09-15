<?php

declare(strict_types=1);

namespace Hirtz\Cms\Sitemap;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Db\ActiveRecord;
use Override;

/**
 * @extends RecordSitemap<Entry>
 */
class EntrySitemap extends RecordSitemap
{
    /**
     * @var bool whether the image assets of an entry should be added to its sitemap URL.
     */
    public bool $enableImages = false;

    public string $modelClass = Entry::class;

    /**
     * @return EntryQuery<Entry>
     */
    #[Override]
    protected function getQuery(): EntryQuery
    {
        $query = Entry::find()
            ->selectSitemapAttributes()
            ->enabled()
            ->withPermalinks()
            ->orderBy(['id' => SORT_ASC]);

        if ($this->enableImages) {
            $query->withSitemapAssets();
        }

        return $query;
    }

    /**
     * @param Entry $record
     */
    #[Override]
    protected function getRecordUrl(ActiveRecord $record, array $route, ?string $language = null): array
    {
        return [
            ...parent::getRecordUrl($record, $route, $language),
            'images' => $this->getImages($record, $language),
        ];
    }

    /**
     * The assets are eager loaded by {@see getQuery()}, so reading the relation must never query: an entry loaded
     * without them has none.
     *
     * @return list<array<string, mixed>>
     */
    protected function getImages(Entry $entry, ?string $language = null): array
    {
        /** @var Asset[] $assets */
        $assets = $entry->getRelatedRecords()['assets'] ?? [];
        $images = [];

        foreach ($assets as $asset) {
            if ($image = $asset->getSitemapUrl($language)) {
                $images[] = $image;
            }
        }

        return $images;
    }
}
