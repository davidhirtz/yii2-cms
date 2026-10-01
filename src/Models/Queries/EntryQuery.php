<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use BackedEnum;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Media\Models\Queries\AssetQuery;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Tenant\Models\Queries\Traits\TenantQueryTrait;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;
use yii\db\Expression;
use yii\db\Query;

/**
 * @template T of Entry
 * @extends I18nActiveQuery<T>
 */
class EntryQuery extends I18nActiveQuery
{
    use ModuleTrait;
    use TenantQueryTrait;

    public function withPermalinks(): static
    {
        return $this->with('permalinks');
    }

    /**
     * `menu_ids` is a JSON list and neither MySQL nor MariaDB can index one usefully, so the condition is a
     * comparison per menu — and the common case, "every declared menu", is the cheap `IS NOT NULL` instead.
     */
    public function andWhereMenu(int|BackedEnum ...$menus): static
    {
        $menuIds = array_values(array_unique(array_map(Menu::getValue(...), $menus)));

        if (!$menuIds) {
            return $this->andWhere('0=1');
        }

        $column = Entry::tableName() . '.[[menu_ids]]';

        if (!array_diff(array_keys(static::getModule()->getMenus()), $menuIds)) {
            return $this->andWhere(['not', [$column => null]]);
        }

        $conditions = ['or'];

        foreach ($menuIds as $key => $menuId) {
            $conditions[] = new Expression("JSON_CONTAINS($column, :menu$key)", [":menu$key" => (string)$menuId]);
        }

        return $this->andWhere($conditions);
    }

    /**
     * Filtered like its counterpart `whereStatus()`: an unset status is no filter, where `andWhere()` would compare
     * against `NULL` and leave nothing behind — an empty menu on any page whose controller had not scoped a query
     * by status first.
     */
    public function andWhereParentStatus(): static
    {
        return $this->andFilterWhere(['>=', Entry::tableName() . '.[[parent_status]]', self::$status]);
    }

    /**
     * The frontend asks for its status here — enabled on the site, draft on the draft domain — so an entry dated in
     * the future stays out of every page, menu, relation and sitemap until its time, and still previews on the
     * draft domain. The admin asks for no status and sees everything.
     */
    #[Override]
    public function whereStatus(?int $status = null): static
    {
        parent::whereStatus($status);

        return self::$status !== null && self::$status >= Entry::STATUS_ENABLED
            ? $this->wherePublished()
            : $this;
    }

    /**
     * Rounded up to the end of the minute, so the statement — and the query and page caches keyed on it — holds for
     * one: a scheduled entry goes live up to a minute early, and one saved this very second is never hidden. A type
     * that does not schedule ({@see EntryType::schedules()}) keeps the date for display: it gates nothing there.
     */
    public function wherePublished(): static
    {
        $modelClass = $this->modelClass;
        $column = $modelClass::tableName() . '.[[publish_date]]';

        $condition = ['or', [$column => null], ['<=', $column, gmdate('Y-m-d H:i:59')]];

        if ($types = static::getUnscheduledTypes($modelClass)) {
            $condition[] = [$modelClass::tableName() . '.[[type]]' => $types];
        }

        return $this->andWhere($condition);
    }

    /**
     * @param class-string<Entry> $modelClass
     * @return list<int>
     */
    public static function getUnscheduledTypes(string $modelClass): array
    {
        $types = [];

        foreach ($modelClass::getTypeDefinitions() as $value => $type) {
            if ($type instanceof EntryType && !$type->schedules()) {
                $types[] = (int)$value;
            }
        }

        return $types;
    }

    /**
     * @param class-string<Entry> $modelClass
     * @return list<int>
     * @deprecated use {@see static::getUnscheduledTypes()}
     */
    public static function getTypesWithoutPublishDate(string $modelClass): array
    {
        return static::getUnscheduledTypes($modelClass);
    }

    #[Override]
    public function enabled(): static
    {
        return $this->whereStatus(Entry::STATUS_DEFAULT)
            ->andWhereParentStatus();
    }

    /**
     * Override this method to select only the attributes needed for frontend display.
     */
    public function selectSiteAttributes(): static
    {
        $attributes = array_diff(
            $this->getModelInstance()->getColumnAttributes(),
            ['updated_by_user_id', 'created_at']
        );

        return $this->addSelect($this->prefixColumns($attributes));
    }

    /**
     * Override this method to select only the attributes needed for XML sitemap generation.
     */
    public function selectSitemapAttributes(): static
    {
        return $this->addSelect($this->prefixColumns([
            'id',
            'status',
            'type',
            'tenant_id',
            'parent_id',
            'section_count',
            'entry_count',
            'updated_at',
        ]))->andWhereCurrentTenant();
    }

    public function matching(?string $search): static
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $this->andWhere($this->getI18nAttributeName('name', fallback: true) . ' LIKE :search', [':search' => "%$search%"]);
        }

        return $this;
    }

    public function whereHasDescendantsEnabled(): static
    {
        return $this->whereNotUri(static::getModule()->entryIndexSlug ?: null);
    }

    public function whereCategory(Category|int $category, bool $eagerLoading = false): static
    {
        if ($category instanceof Category) {
            $orderBy = $category->getEntriesOrderBy();

            if (is_array($orderBy)) {
                $this->orderBy($orderBy);
            }

            return $this->innerJoinWithEntryCategory((int)$category->id, $eagerLoading);
        }

        return $this->innerJoinWithEntryCategory($category, $eagerLoading);
    }

    /**
     * @param list<Category|int|string> $categories
     * @noinspection PhpUnused
     */
    public function whereCategories(array $categories, bool $eagerLoading = false): static
    {
        foreach ($categories as $category) {
            $categoryId = $category instanceof Category ? $category->id : $category;
            $this->innerJoinWithEntryCategory((int)$categoryId, $eagerLoading, true);
        }

        return $this;
    }

    /**
     * Prepends alias to inner join to allow multiple categories. Keeps original table name for single joins to use of
     * {@see Category::getEntriesOrderBy()} order; a single eager-loaded join reads the record off the row.
     */
    protected function innerJoinWithEntryCategory(int $categoryId, bool $eagerLoading = false, bool $useAlias = false): static
    {
        $onCondition = function (ActiveQuery $query) use ($categoryId, $useAlias): void {
            $query->onCondition([($useAlias ? "[[entryCategory$categoryId]]" : EntryCategory::tableName()) . '.[[category_id]]' => $categoryId]);
        };

        if ($eagerLoading && !$useAlias) {
            return $this->selectWith('entryCategory', 'INNER JOIN', $onCondition);
        }

        return $this->innerJoinWith([
            ($useAlias ? "entryCategory entryCategory$categoryId" : 'entryCategory') => $onCondition,
        ], $eagerLoading);
    }

    public function whereRelatedModel(EntryRelationModelInterface $model, string $joinType = 'INNER JOIN'): static
    {
        $tableName = EntryRelation::tableName();

        if ($joinType === 'INNER JOIN') {
            $this->orderBy($model->getEntriesOrderBy() ?? ["$tableName.[[position]]" => SORT_ASC]);
        }

        return $this->selectWith(
            'entryRelation',
            $joinType,
            fn (ActiveQuery $query) => $query->onCondition([
                "$tableName.[[model_class]]" => $model->getEntryRelationClass()::getModelClass(),
                "$tableName.[[model_id]]" => $model->id,
            ]),
        );
    }

    public function whereIndex(): static
    {
        return $this->whereUri((string)static::getModule()->entryIndexSlug);
    }

    public function whereId(int $id): static
    {
        return $this->andWhere([Entry::tableName() . '.[[id]]' => $id]);
    }

    /**
     * A per-language record wins over the {@see Permalink::LANGUAGE_ALL} one for the same path. The matched record is
     * handed to the entry, so the relation is not loaded to read it back. The permalink's own copy of the tenant is
     * what lets the lookup use its unique key, which leads with it.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        $language ??= Yii::$app->language;
        $alias = Permalink::tableName();

        $manager = Yii::$app->getUrlManager();
        $tenant = $manager instanceof UrlManager ? $manager->tenant : null;

        return $this->innerJoin($alias, "$alias.[[entry_id]] = " . Entry::tableName() . '.[[id]]')
            ->selectJoinedRecord('permalink', Permalink::find(), $alias, function (Entry $entry, ?Permalink $permalink): void {
                if ($permalink) {
                    $entry->populatePermalink($permalink);
                }
            })
            ->andWhere([
                ...$tenant ? ["$alias.[[tenant_id]]" => $tenant->id] : [],
                "$alias.[[uri]]" => trim($uri, '/'),
                "$alias.[[language]]" => [$language, Permalink::LANGUAGE_ALL],
            ])
            ->addOrderBy(new Expression("$alias.[[language]] = :permalinkLanguage DESC", [
                ':permalinkLanguage' => $language,
            ]))
            ->andWhereCurrentTenant();
    }

    public function whereNotUri(?string $uri): static
    {
        if (!$uri) {
            return $this;
        }

        return $this->andWhere([
            'not in',
            Entry::tableName() . '.[[id]]',
            (new Query())
                ->select('entry_id')
                ->from(Permalink::tableName())
                ->where([
                    'uri' => trim($uri, '/'),
                    'language' => [Yii::$app->language, Permalink::LANGUAGE_ALL],
                ]),
        ]);
    }

    public function withSitemapAssets(): static
    {
        return $this->with([
            'assets' => function (AssetQuery $query): void {
                $query->selectSitemapAttributes()
                    ->whereStatus()
                    ->withFiles();
            },
        ]);
    }
}
