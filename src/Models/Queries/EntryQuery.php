<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Media\Models\Queries\AssetQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Tenant\Models\Queries\Traits\TenantQueryTrait;
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

    private const string PERMALINK_COLUMN_PREFIX = 'permalink__';

    private bool $populateMatchedPermalink = false;

    public function withPermalinks(): static
    {
        return $this->with('permalinks');
    }

    public function andWhereParentStatus(): static
    {
        return $this->andWhere(['>=', Entry::tableName() . '.[[parent_status]]', self::$_status]);
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

    public function whereCategory(array|Category|int $category, bool $eagerLoading = false): static
    {
        if ($category instanceof Category) {
            if ($orderBy = $category->getEntriesOrderBy()) {
                $this->orderBy($orderBy);
            }
        }

        return $this->innerJoinWithEntryCategory($category->id ?? $category, $eagerLoading);
    }

    /**
     * @noinspection PhpUnused
     */
    public function whereCategories(array $categories, bool $eagerLoading = false): static
    {
        foreach ($categories as $category) {
            $this->innerJoinWithEntryCategory($category->id ?? $category, $eagerLoading, true);
        }

        return $this;
    }

    /**
     * Prepends alias to inner join to allow multiple categories. Keeps original table name for single joins to use of
     * {@see Category::getEntriesOrderBy()} order.
     */
    protected function innerJoinWithEntryCategory(int $categoryId, bool $eagerLoading = false, bool $useAlias = false): static
    {
        return $this->innerJoinWith([
            ($useAlias ? "entryCategory entryCategory$categoryId" : 'entryCategory') => function (ActiveQuery $query) use ($categoryId, $useAlias): void {
                $query->onCondition([($useAlias ? "[[entryCategory$categoryId]]" : EntryCategory::tableName()) . '.[[category_id]]' => $categoryId]);
            }
        ], $eagerLoading);
    }

    public function whereSection(Section $section, bool $eagerLoading = true, string $joinType = 'INNER JOIN'): static
    {
        $tableName = SectionEntry::tableName();
        $onCondition = fn (ActiveQuery $query) => $query->onCondition(["$tableName.[[section_id]]" => $section->id]);

        if ($eagerLoading && $joinType === 'INNER JOIN') {
            $orderBy = $section->getEntriesOrderBy() ?? [SectionEntry::tableName() . '.[[position]]' => SORT_ASC];
            $this->orderBy($orderBy);
        }

        return $this->joinWith(['sectionEntry' => $onCondition], $eagerLoading, $joinType);
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
     * A per-language record wins over the {@see Permalink::LANGUAGE_ALL} one for the same path. The matched record
     * is handed to the entry, so the relation is not loaded to read it back; a query without a select of its own
     * gets the entry's columns, or the joined `id` would overwrite the entry's.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        $language ??= Yii::$app->language;
        $alias = Permalink::tableName();
        $columns = [];

        if (empty($this->select)) {
            $this->select(Entry::tableName() . '.*');
        }

        foreach (Permalink::getTableSchema()->getColumnNames() as $name) {
            $columns[self::PERMALINK_COLUMN_PREFIX . $name] = "$alias.[[$name]]";
        }

        $this->populateMatchedPermalink = true;

        return $this->innerJoin($alias, "$alias.[[entry_id]] = " . Entry::tableName() . '.[[id]]')
            ->addSelect($columns)
            ->andWhere([
                "$alias.[[uri]]" => trim($uri, '/'),
                "$alias.[[language]]" => [$language, Permalink::LANGUAGE_ALL],
            ])
            ->addOrderBy(new Expression("$alias.[[language]] = :permalinkLanguage DESC", [
                ':permalinkLanguage' => $language,
            ]))
            ->andWhereCurrentTenant();
    }

    #[Override]
    public function populate($rows): array
    {
        $models = parent::populate($rows);

        if (!$this->populateMatchedPermalink || $this->asArray || count($models) !== count($rows)) {
            return $models;
        }

        $prefix = self::PERMALINK_COLUMN_PREFIX;

        foreach (array_values($models) as $index => $model) {
            $attributes = [];

            foreach (array_values($rows)[$index] as $name => $value) {
                if (str_starts_with((string)$name, $prefix)) {
                    $attributes[substr((string)$name, strlen($prefix))] = $value;
                }
            }

            if (!$model instanceof Entry || !isset($attributes['id'])) {
                continue;
            }

            $permalink = Permalink::instantiate($attributes);
            Permalink::populateRecord($permalink, $attributes);
            $permalink->afterFind();

            $model->populatePermalink($permalink);
        }

        return $models;
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
