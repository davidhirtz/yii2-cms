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
     * @var int|false duration in seconds for the caching the Category::getCategories() category query
     */
    public int|false $categoryCachedQueryDuration = 60;

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
     * @var array|null the default sort order when neither type nor category previously applied an order
     */
    public ?array $defaultEntryOrderBy;

    /**
     * @var int|null the default entry type which is applied to all default admin urls
     */
    public ?int $defaultEntryType = null;

    /**
     * @var bool whether image assets should be added to CML sitemap URLs
     */
    public bool $enableImageSitemaps = false;

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