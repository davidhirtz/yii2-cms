<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\I18nActiveQuery;
use Override;

/**
 * @template T of Entry
 * @template-extends I18nActiveQuery<T>
 */
class EntryQuery extends I18nActiveQuery
{
    use ModuleTrait;

    public function addSelectI18nSlugTargetAttributes(): static
    {
        if ($slugTargetAttribute = Entry::instance()->slugTargetAttribute) {
            $this->addSelect($this->prefixColumns(Entry::instance()->getI18nAttributesNames($slugTargetAttribute)));
        }

        return $this;
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
        return $this->addSelect($this->prefixColumns(array_diff(
            $this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at']
        )));
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
            ...Entry::instance()->getI18nAttributesNames(['slug', 'parent_slug']),
            'section_count',
            'updated_at',
        ]));
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
        return $this->andWhere([
            '!=',
            $this->getI18nAttributeName('slug'),
            static::getModule()->entryIndexSlug,
        ]);
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
            ($useAlias ? "entryCategory entryCategory$categoryId" : 'entryCategory') => function (ActiveQuery $query) use ($categoryId, $useAlias) {
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

    public function whereSlug(string $slug): static
    {
        if (in_array('parent_slug', (array)Entry::instance()->slugTargetAttribute, true)) {
            $slug = explode('/', $slug);

            return $this->andWhere([
                $this->getI18nAttributeName('slug') => array_pop($slug),
                $this->getI18nAttributeName('parent_slug') => implode('/', $slug) ?: null,
            ]);
        }

        return $this->andWhere([$this->getI18nAttributeName('slug') => trim($slug, '/')]);
    }

    public function withSitemapAssets(): static
    {
        return $this->with([
            'assets' => function (AssetQuery $query) {
                $query->selectSitemapAttributes()
                    ->replaceI18nAttributes()
                    ->whereStatus()
                    ->withFiles();
            },
        ]);
    }
}
