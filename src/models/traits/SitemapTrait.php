<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
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

        /** @var self $record */
        foreach ($query->each() as $record) {
            foreach ($languages as $language) {
                if ($language) {
                    // Temporarily set location for I18n attributes to work
                    Yii::$app->language = $language;
                }

                if ($url = $record->getSitemapUrl($language)) {
                    $urls [] = $url;
                }
            }
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
     * Returns an array of languages used for I18N URLs. This is only intended for {@see ActiveRecord::$i18nAttributes}
     * tables and not for {@see Module::$enableI18nTables} as the website structure might be different and thus rather
     * single sitemaps per language should be submitted.
     */
    protected function getSitemapLanguages(): array
    {
        $manager = Yii::$app->getUrlManager();
        return $this->i18nAttributes && $manager->hasI18nUrls() ? array_keys($manager->languages) : [null];
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
