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
use Hirtz\Tenant\Models\Tenant;
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
     * `menu_ids` is a JSON list no index serves, so each menu is a comparison and every declared menu the cheap
     * `IS NOT NULL`.
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
     * An unset status is no filter, where `andWhere()` would compare against `NULL` and leave nothing.
     */
    public function andWhereParentStatus(): static
    {
        return $this->andFilterWhere(['>=', Entry::tableName() . '.[[parent_status]]', self::$status]);
    }

    /**
     * The frontend asks for its status here, so an entry dated in the future stays off the site until its time and
     * still previews on the draft domain.
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
     * Rounded up to the end of the minute so the caches keyed on it hold for one; a type that does not
     * {@see EntryType::schedules()} is not gated.
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

    public function selectSiteAttributes(): static
    {
        $attributes = array_diff(
            $this->getModelInstance()->getColumnAttributes(),
            ['updated_by_user_id', 'created_at']
        );

        return $this->addSelect($this->prefixColumns($attributes));
    }

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
     * An alias allows several categories, while a single join keeps the table name for
     * {@see Category::getEntriesOrderBy()} and an eager-loaded one reads the record off the row.
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

    public function whereIndex(?Tenant $tenant = null): static
    {
        return $this->whereUri((string)static::getModule()->entryIndexSlug, tenant: $tenant);
    }

    public function whereId(int $id): static
    {
        return $this->andWhere([Entry::tableName() . '.[[id]]' => $id]);
    }

    /**
     * A per-language record wins over the {@see Permalink::LANGUAGE_ALL} one, and the permalink's copy of the tenant
     * lets the lookup use its unique key.
     */
    public function whereUri(string $uri, ?string $language = null, ?Tenant $tenant = null): static
    {
        $language ??= Yii::$app->getI18n()->getContentLanguage();
        $alias = Permalink::tableName();

        $manager = Yii::$app->getUrlManager();
        $tenant ??= $manager instanceof UrlManager ? $manager->tenant : null;

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
            ->andWhere($tenant ? [Entry::tableName() . '.[[tenant_id]]' => $tenant->id] : []);
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
