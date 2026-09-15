<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Closure;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Skeleton\Filters\PageCache;
use Override;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

class Module extends \Hirtz\Skeleton\Base\Module
{
    final public const string AUTH_ROLE_AUTHOR = 'author';

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
     * @var bool whether the default url rules should be loaded automatically, defaults to true
     */
    public bool $enableUrlRules = true;

    /**
     * @var array<string, int>|null the default sort order when neither type nor category previously applied an order
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

    /**
     * @var Closure(): array<mixed>|array<mixed> the declaration, which is unvalidated by definition
     */
    private Closure|array $configuredSectionSets = [];

    /**
     * @var array<int, SectionSet>|null
     */
    private ?array $sectionSets = null;

    #[Override]
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

    /**
     * A closure, because a set's name is a {@see Yii::t()} result and a configuration file is read before the
     * application has an `i18n` component. A plain list is accepted for a declaration that needs no translation.
     *
     * @param Closure(): array<mixed>|array<mixed> $sectionSets
     */
    public function setSectionSets(Closure|array $sectionSets): void
    {
        $this->configuredSectionSets = $sectionSets;
        $this->sectionSets = null;
    }

    /**
     * @return array<int, SectionSet>
     */
    public function getSectionSets(): array
    {
        if ($this->sectionSets !== null) {
            return $this->sectionSets;
        }

        $declared = $this->configuredSectionSets instanceof Closure
            ? ($this->configuredSectionSets)()
            : $this->configuredSectionSets;

        $sectionSets = [];

        foreach ($declared as $set) {
            if (!$set instanceof SectionSet) {
                $given = get_debug_type($set);
                throw new InvalidConfigException(static::class . '::$sectionSets must be a list of ' . SectionSet::class . ", got $given.");
            }

            if (isset($sectionSets[$set->value])) {
                throw new InvalidConfigException(static::class . "::\$sectionSets declares \"$set->value\" twice.");
            }

            $set->validate();
            $sectionSets[$set->value] = $set;
        }

        return $this->sectionSets = $sectionSets;
    }

    public function findSectionSet(?int $value): ?SectionSet
    {
        return $value === null ? null : ($this->getSectionSets()[$value] ?? null);
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
