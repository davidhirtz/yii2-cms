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
 * @method Entry one($db = null)
 */
class EntryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return EntryQuery
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at']));
    }

    /**
     * @param string $search
     * @return EntryQuery
     */
    public function matching($search)
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $this->andWhere(Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('name') . ']] LIKE :search', [':search' => "%{$search}%"]);
        }

        return $this;
    }

    /**
     * @param int[]|Category $category
     * @param bool $eagerLoading
     * @return EntryQuery
     */
    public function whereCategory($category, $eagerLoading = false)
    {
        if ($orderBy = $category->getEntryOrderBy()) {
            $this->orderBy($orderBy);
        }

        return $this->innerJoinWith([
            'entryCategory' => function (ActiveQuery $query) use ($category) {
                $query->onCondition([EntryCategory::tableName() . '.[[category_id]]' => $category->id ?? $category]);
            }
        ], $eagerLoading);
    }

    /**
     * @param int[]|Category[] $categories
     * @param bool $eagerLoading
     * @return EntryQuery
     */
    public function whereCategories($categories, $eagerLoading = false)
    {
        foreach ($categories as $category) {
            $categoryId = $category->id ?? $category;
            $this->innerJoinWith([
                'entryCategory entryCategory' . $categoryId => function (ActiveQuery $query) use ($categoryId) {
                    $query->onCondition(['entryCategory' . $categoryId . '.[[category_id]]' => $categoryId]);
                }
            ], $eagerLoading);
        }

        return $this->addSelectPrefixed(['position', 'updated_at']);
    }

    /**
     * @param string $slug
     * @return EntryQuery
     */
    public function whereSlug($slug)
    {
        return $this->whereLower([Entry::tableName() . '.[[' . Entry::instance()->getI18nAttributeName('slug') . ']]' => $slug]);
    }

    /**
     * @return EntryQuery
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
     * @return EntryQuery
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