<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Entry[] all($db = null)
 * @method Entry[] each($batchSize = 100, $db = null)
 * @method Entry one($db = null)
 */
class EntryQuery extends ActiveQuery
{
    /**
     * @return $this
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at'])));
    }

    /**
     * @return $this
     */
    public function selectSitemapAttributes()
    {
        return $this->addSelect($this->prefixColumns(array_merge(
            ['id', 'status', 'type', 'updated_at'],
            Entry::instance()->getI18nAttributesNames(['slug'])
        )));
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching(string $search)
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $this->andWhere(Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('name') . ']] LIKE :search', [':search' => "%{$search}%"]);
        }

        return $this;
    }

    /**
     * @param int|int[]|Category $category
     * @param bool $eagerLoading
     * @return $this
     */
    public function whereCategory($category, $eagerLoading = false)
    {
        if ($category instanceof Category) {
            if ($orderBy = $category->getEntryOrderBy()) {
                $this->orderBy($orderBy);
            }
        }

        return $this->innerJoinWithEntryCategory($category->id ?? $category, $eagerLoading);
    }

    /**
     * @param int[]|Category[] $categories
     * @param bool $eagerLoading
     * @return $this
     */
    public function whereCategories(array $categories, $eagerLoading = false)
    {
        foreach ($categories as $category) {
            $this->innerJoinWithEntryCategory($category->id ?? $category, $eagerLoading, true);
        }

        return $this;
    }

    /**
     * Prepends alias to inner join to allow multiple categories. Keeps original table name
     * for single joins to use of {@link Category::getEntryOrderBy()} order.
     *
     * @param int $categoryId
     * @param bool $eagerLoading
     * @param false $useAlias
     * @return $this
     */
    protected function innerJoinWithEntryCategory($categoryId, $eagerLoading = false, $useAlias = false)
    {
        return $this->innerJoinWith([
            ($useAlias ? "entryCategory entryCategory{$categoryId}" : 'entryCategory') => function (ActiveQuery $query) use ($categoryId, $useAlias) {
                $query->onCondition([($useAlias ? "[[entryCategory{$categoryId}]]" : EntryCategory::tableName()) . '.[[category_id]]' => $categoryId]);
            }
        ], $eagerLoading);
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function whereSlug(string $slug)
    {
        return $this->andWhere([Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('slug') . ']]' => $slug]);
    }

    /**
     * @return $this
     */
    public function withAssets()
    {
        return $this->with([
            'assets' => function (AssetQuery $query) {
                if (!isset($this->with['sections'])) {
                    $query->andWhere(['section_id' => null]);
                }

                $query->selectSiteAttributes()
                    ->replaceI18nAttributes()
                    ->whereStatus()
                    ->withFiles();
            },
        ]);
    }

    /**
     * @return $this
     */
    public function withSections()
    {
        return $this->with([
            'sections' => function (SectionQuery $query) {
                $query->selectSiteAttributes()
                    ->replaceI18nAttributes()
                    ->whereStatus();
            }
        ]);
    }
}