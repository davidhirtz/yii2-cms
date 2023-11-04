<?php

namespace davidhirtz\yii2\cms;

use davidhirtz\yii2\skeleton\filters\PageCache;
use davidhirtz\yii2\skeleton\modules\ModuleTrait;
use Yii;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

/**
 * The CMS Module.
 */
class Module extends \yii\base\Module
{
    use ModuleTrait;

    public const AUTH_ROLE_AUTHOR = 'author';

    /**
     * @var bool whether categories should be enabled
     */
    public bool $enableCategories = false;

    /**
     * @var bool whether categories should be stored in a nested tree
     */
    public bool $enableNestedCategories = true;

    /**
     * @var bool whether entries should automatically inherit parent categories
     */
    public bool $inheritNestedCategories = true;

    /**
     * @var bool whether entries should be nested
     */
    public bool $enableNestedEntries = false;

    /**
     * @var bool whether entries should have sections
     */
    public bool $enableSections = true;

    /**
     * @var bool whether entries should have assets
     */
    public bool $enableEntryAssets = true;

    /**
     * @var bool whether sections should have assets
     */
    public bool $enableSectionAssets = true;

    /**
     * @var bool whether entries should be linkable to sections
     * @since 1.4.0
     */
    public bool $enableSectionEntries = false;

    /**
     * @var bool whether image assets should be added to CML sitemap URLs
     */
    public bool $enableImageSitemaps = false;

    /**
     * @var bool whether the default url rules should be loaded automatically, defaults to true
     */
    public bool $enableUrlRules = true;

    /**
     * @var array|null the default sort order when neither type nor category previously applied an order
     */
    public ?array $defaultEntryOrderBy = null;

    /**
     * @var int|null the default entry type which is applied to all default admin urls
     */
    public ?int $defaultEntryType = null;

    /**
     * @var int|false duration in seconds for caching the category query
     * @see CategoryCollection::getCategories()
     */
    public int|false $categoryCachedQueryDuration = 60;

    /**
     * @var string|false the default entry slug that will be omitted from the url and redirected to the index action.
     * Set false to disable this feature.
     */
    public string|false $entryIndexSlug = 'home';

    public function init(): void
    {
        if (!$this->enableSections) {
            $this->enableSectionAssets = false;
        }

        if (!$this->enableCategories) {
            $this->enableNestedCategories = false;
        }

        if (!$this->enableNestedCategories) {
            $this->inheritNestedCategories = false;
        }

        parent::init();
    }

    public function invalidatePageCache(): void
    {
        if ($cache = $this->getCache()) {
            TagDependency::invalidate($cache, PageCache::TAG_DEPENDENCY_KEY);
        }
    }

    public function getCache(): ?CacheInterface
    {
        return Yii::$app->getCache();
    }
}