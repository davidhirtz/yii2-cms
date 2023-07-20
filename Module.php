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
    public $enableCategories = false;

    /**
     * @var bool whether categories should be stored in a nested tree
     */
    public $enableNestedCategories = true;

    /**
     * @var bool whether entries should automatically inherit parent categories
     */
    public $inheritNestedCategories = true;

    /**
     * @var int duration in seconds for the caching the Category::getCategories() category query
     */
    public $categoryCachedQueryDuration = 60;

    /**
     * @var bool whether entries should have sections
     */
    public $enableSections = true;

    /**
     * @var bool whether entries should have assets
     */
    public $enableEntryAssets = true;

    /**
     * @var bool whether sections should have assets
     */
    public $enableSectionAssets = true;

    /**
     * @var array the default sort when neither type nor category apply an order
     */
    public $defaultEntryOrderBy;

    /**
     * @var int the default entry type which is applied to all default admin urls
     */
    public $defaultEntryType;

    /**
     * @todo currently not fully implemented
     * @var array the default category type which is applied to all default admin urls
     */
    public $defaultCategoryType;

    /**
     * @var bool whether image assets should be added to CML sitemap URLs
     */
    public $enableImageSitemaps = false;

    /**
     * @inheritDoc
     */
    public function init()
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
    /**
     * @return void
     */
    public function invalidatePageCache(): void
    {
        if ($cache = $this->getCache()) {
            TagDependency::invalidate($cache, PageCache::TAG_DEPENDENCY_KEY);
        }
    }

    /**
     * @return CacheInterface|null
     */
    public function getCache()
    {
        return Yii::$app->getCache();
    }
}