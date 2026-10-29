<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Closure;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Skeleton\Filters\PageCache;
use Override;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use Hirtz\Skeleton\Caching\TagDependency;

class Module extends \Hirtz\Skeleton\Base\Module
{
    final public const string AUTH_ROLE_AUTHOR = 'author';

    public bool $enableCategories = false;

    public bool $enableNestedCategories = true;

    public bool $inheritNestedCategories = true;

    public bool $enableNestedEntries = false;

    public bool $enableSections = true;

    public bool $enableEntryAssets = true;

    public bool $enableSectionAssets = true;

    public bool $enableSectionEntries = false;

    /**
     * A section places a global block by carrying a `block_id`.
     */
    public bool $enableBlocks = true;

    public bool $enableBlockAssets = true;

    /**
     * `null` follows {@see $enableSectionEntries}.
     */
    public ?bool $enableBlockEntries = null;

    /**
     * @var list<class-string<EntryRelation>>
     */
    public array $entryRelations = [];

    public bool $enableUrlRules = true;

    /**
     * Applies when neither type nor category set an order.
     *
     * @var array<string, int>|null
     */
    public ?array $defaultEntryOrderBy = ['position' => SORT_ASC];

    /**
     * Applied to every default admin URL.
     */
    public ?int $defaultEntryType = null;

    /**
     * `false` disables the cache.
     *
     * @see CategoryCollection::getAll()
     */
    public int|null|false $categoryCachedQueryDuration = 60;

    /**
     * The slug omitted from the URL and redirected to the index action, `false` for none.
     */
    public string|false $entryIndexSlug = 'home';

    /**
     * @var Closure(): array<mixed>|array<mixed>
     */
    private Closure|array $configuredSectionSets = [];

    /**
     * @var array<int, SectionSet>|null
     */
    private ?array $sectionSets = null;

    /**
     * @var Closure(): array<mixed>|array<mixed>
     */
    private Closure|array $configuredMenus = [];

    /**
     * @var array<int, Menu>|null
     */
    private ?array $menus = null;

    #[Override]
    public function init(): void
    {
        if (!$this->enableSections) {
            $this->enableSectionAssets = false;
            $this->enableBlocks = false;
        }

        $this->enableBlockEntries ??= $this->enableSectionEntries;

        if (!$this->enableBlocks) {
            $this->enableBlockAssets = false;
            $this->enableBlockEntries = false;
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
     * A closure, since a set's name is a {@see Yii::t()} result and the configuration is read before the `i18n`
     * component exists.
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

    /**
     * A closure, since a menu's name is a {@see Yii::t()} result and the configuration is read before the `i18n`
     * component exists.
     *
     * @param Closure(): array<mixed>|array<mixed> $menus
     */
    public function setMenus(Closure|array $menus): void
    {
        $this->configuredMenus = $menus;
        $this->menus = null;
    }

    /**
     * @return array<int, Menu>
     */
    public function getMenus(): array
    {
        if ($this->menus !== null) {
            return $this->menus;
        }

        $declared = $this->configuredMenus instanceof Closure
            ? ($this->configuredMenus)()
            : $this->configuredMenus;

        $menus = [];

        foreach ($declared as $menu) {
            if (!$menu instanceof Menu) {
                $given = get_debug_type($menu);
                throw new InvalidConfigException(static::class . '::$menus must be a list of ' . Menu::class . ", got $given.");
            }

            if (isset($menus[$menu->value])) {
                throw new InvalidConfigException(static::class . "::\$menus declares \"$menu->value\" twice.");
            }

            $menu->validate();
            $menus[$menu->value] = $menu;
        }

        return $this->menus = $menus;
    }

    public function findMenu(?int $value): ?Menu
    {
        return $value === null ? null : ($this->getMenus()[$value] ?? null);
    }

    /**
     * @param class-string|string|null $modelClass
     * @return class-string<EntryRelation>|null
     */
    public function getEntryRelationClass(?string $modelClass): ?string
    {
        if ($modelClass === null) {
            return null;
        }

        foreach ($this->getEntryRelationClasses() as $class) {
            if ($class::getModelClass() === $modelClass) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @return list<class-string<EntryRelation>>
     */
    public function getEntryRelationClasses(): array
    {
        return $this->entryRelations;
    }

    /**
     * Cached under the page cache's tag, so a save recomputes it, and recomputed once the moment has passed.
     */
    public function getNextPublishTime(): ?int
    {
        $cache = $this->getCache();
        $key = [self::class, 'next-publish-time'];
        $time = $cache?->get($key);

        if (!is_int($time) || ($time > 0 && $time <= time())) {
            $time = $this->findNextPublishTime() ?? 0;

            $cache?->set($key, $time, 0, new TagDependency(['tags' => [PageCache::TAG_DEPENDENCY_KEY]]));
        }

        return $time > 0 ? $time : null;
    }

    protected function findNextPublishTime(): ?int
    {
        $query = Entry::find()
            ->select(['MIN([[publish_date]])'])
            ->andWhere(['>=', 'status', Entry::STATUS_ENABLED])
            ->andWhere(['>', 'publish_date', gmdate('Y-m-d H:i:s')]);

        if ($types = EntryQuery::getUnscheduledTypes(Entry::class)) {
            $query->andWhere(['not in', 'type', $types]);
        }

        $date = $query->scalar();

        return is_string($date) ? (int)strtotime("$date UTC") : null;
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
