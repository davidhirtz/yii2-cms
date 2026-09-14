<?php

declare(strict_types=1);

namespace Hirtz\Cms\Sitemap;

use Hirtz\Cms\Models\ActiveRecord as CmsActiveRecord;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Sitemap\ModelSitemap;
use Override;

/**
 * @template T of CmsActiveRecord
 * @extends ModelSitemap<T>
 */
abstract class RecordSitemap extends ModelSitemap
{
    /**
     * @param T $record
     */
    #[Override]
    protected function getRecordUrls(ActiveRecord $record, ?string $language = null): array
    {
        $route = $record->getRoute();

        return $route ? [$this->normalizeUrl($this->getRecordUrl($record, $route, $language))] : [];
    }

    /**
     * @param T $record
     * @param array<array-key, mixed> $route
     * @return array<string, mixed>
     */
    protected function getRecordUrl(ActiveRecord $record, array $route, ?string $language = null): array
    {
        return [
            'loc' => $route + ['language' => $language],
            'lastmod' => $record->updated_at,
        ];
    }
}
