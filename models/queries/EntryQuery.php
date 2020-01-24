<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Entry;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
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
            $this->andWhere(Entry::tableName() . '.[[name]] LIKE :search', [':search' => "%{$search}%"]);
        }

        return $this;
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