<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Override;
use Yii;
use yii\db\Query;

/**
 * @template T of Entry
 * @extends I18nActiveQuery<T>
 */
class EntryQuery extends I18nActiveQuery
{
    use ModuleTrait;

    /**
     * The virtual slug is reported by `attributes()` but has no column, so it must not reach the SELECT.
     */
    #[Override]
    public function selectAllColumns(): static
    {
        $this->select = $this->prefixColumns($this->getModelInstance()->getColumnAttributes());
        return $this;
    }

    /**
     * Slugs live in {@see Permalink} records now, so they are eager loaded rather than selected.
     */
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

        return $this->addSelect($this->prefixColumns($attributes))
            ->withPermalinks();
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
            'parent_id',
            'section_count',
            'entry_count',
            'updated_at',
        ]))->withPermalinks();
    }

    public function matching(?string $search): static
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $this->andWhere(Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('name') . ']] LIKE :search', [':search' => "%$search%"]);
        }

        return $this;
    }

    public function whereHasDescendantsEnabled(): static
    {
        return $this->whereNotSlug(static::getModule()->entryIndexSlug);
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
        return $this->whereSlug(static::getModule()->entryIndexSlug);
    }

    public function whereId(int $id): static
    {
        return $this->andWhere([Entry::tableName() . '.[[id]]' => $id]);
    }

    /**
     * Matches the full path against the {@see Permalink} table. Kept for callers that only have a slug; the site
     * controller resolves the permalink first and then loads the entry by id.
     */
    public function whereSlug(string $slug): static
    {
        return $this->andWhere([
            Entry::tableName() . '.[[id]]' => $this->getPermalinkSubQuery()
                ->andWhere(['uri' => trim($slug, '/')]),
        ]);
    }

    public function whereNotSlug(?string $slug): static
    {
        if (!$slug) {
            return $this;
        }

        return $this->andWhere([
            'not in',
            Entry::tableName() . '.[[id]]',
            $this->getPermalinkSubQuery()->andWhere(['uri' => trim($slug, '/')]),
        ]);
    }

    /**
     * @return \yii\db\Query
     */
    protected function getPermalinkSubQuery(): Query
    {
        return (new Query())
            ->select('model_id')
            ->from(Permalink::tableName())
            ->where([
                'model' => $this->getModelInstance()->getPermalinkModelClass(),
                'language' => Yii::$app->language,
            ]);
    }

    public function withSitemapAssets(): static
    {
        return $this->with([
            'assets' => function (AssetQuery $query): void {
                $query->selectSitemapAttributes()
                    ->replaceI18nAttributes()
                    ->whereStatus()
                    ->withFiles();
            },
        ]);
    }
}
