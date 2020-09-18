<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Entry[] all($db = null)
 * @method Entry one($db = null)
 */
class EntryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return $this
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at']));
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
     * @param int|Category $category
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

        return $this->whereCategories([$category], $eagerLoading);
    }

    /**
     * @param int[]|Category[] $categories
     * @param bool $eagerLoading
     * @return $this
     */
    public function whereCategories(array $categories, $eagerLoading = false)
    {
        foreach ($categories as $category) {
            $categoryId = $category->id ?? $category;
            $alias = count($categories) > 1 ? "entryCategory{$categoryId}" : 'entryCategory';

            $this->innerJoinWith([
                "entryCategory {$alias}" => function (ActiveQuery $query) use ($alias, $categoryId) {
                    $query->onCondition(["[[{$alias}]].[[category_id]]" => $categoryId]);
                }
            ], $eagerLoading);
        }

        return $this->addSelectPrefixed(['position', 'updated_at']);
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function whereSlug(string $slug)
    {
        return $this->where([Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('slug') . ']]' => $slug]);
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
                    ->whereStatus();
            }
        ]);
    }
}