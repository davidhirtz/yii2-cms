<?php

namespace davidhirtz\yii2\cms;

use davidhirtz\yii2\cms\models\collections\CategoryCollection;
use davidhirtz\yii2\skeleton\filters\PageCache;
use davidhirtz\yii2\skeleton\modules\ModuleTrait;
use Yii;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

class Module extends \davidhirtz\yii2\skeleton\base\Module
{
    use ModuleTrait;

    final public const AUTH_ROLE_AUTHOR = 'author';

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
    public ?array $defaultEntryOrderBy = ['position' => SORT_ASC];

    /**
     * @var int|null the default entry type which is applied to all default admin urls
     */
    public ?int $defaultEntryType = null;

    /**
     * @var int|null|false duration in seconds for caching the category query. Set to `false` to disable cache.
     * @see CategoryCollection::getAll()
     */
    public int|null|false $categoryCachedQueryDuration = 60;

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
