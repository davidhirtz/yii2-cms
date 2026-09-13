<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Skeleton\Db\ActiveQuery;
use Yii;

trait SitemapTrait
{
    /**
     * @noinspection PhpUnused {@see Sitemap::generateUrls()}
     */
    public function generateSitemapUrls(int $offset = 0): array
    {
        $languages = $this->getSitemapLanguages();
        $sitemap = Yii::$app->sitemap;
        $urls = [];

        $query = $this->getSitemapQuery();

        if ($sitemap->useSitemapIndex) {
            $limit = $sitemap->maxUrlCount / count($languages);
            $query->limit($limit)->offset($offset * $limit);
        }

        // the language is switched per record so the translated attributes resolve, and whatever renders the
        // sitemap runs in the request's own language again
        $previousLanguage = Yii::$app->language;

        try {
            /** @var self $record */
            foreach ($query->each() as $record) {
                foreach ($languages as $language) {
                    if ($language) {
                        Yii::$app->language = $language;
                    }

                    if ($url = $record->getSitemapUrl($language)) {
                        $urls[] = $url;
                    }
                }
            }
        } finally {
            Yii::$app->language = $previousLanguage;
        }

        return $urls;
    }

    /**
     * @noinspection PhpUnused {@see Sitemap::generateIndexUrls()}
     */
    public function getSitemapUrlCount(): int
    {
        $languages = $this->getSitemapLanguages();
        return $this->getSitemapQuery()->count() * count($languages);
    }

    /**
     * Returns an array of languages used for I18N URLs, based on {@see ActiveRecord::$i18nAttributes}.
     */
    protected function getSitemapLanguages(): array
    {
        $manager = Yii::$app->getUrlManager();
        return $this->i18nAttributes && $manager->i18nUrl ? array_keys($manager->languages) : [null];
    }

    /**
     * Returns an array with the attributes needed for the XML sitemap. This can be overridden to add additional fields
     * such as priority or images.
     */
    public function getSitemapUrl(?string $language = null): array|false
    {
        if ($this->includeInSitemap($language)) {
            if ($route = $this->getRoute()) {
                return [
                    'loc' => $route + ['language' => $language],
                    'lastmod' => $this->updated_at,
                ];
            }
        }

        return false;
    }

    public function getSitemapQuery(): ActiveQuery
    {
        return static::find();
    }
}
