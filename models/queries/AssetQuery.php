<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\queries\FileQuery;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class AssetQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Asset[] all($db = null)
 * @method Asset one($db = null)
 */
class AssetQuery extends ActiveQuery
{
    /**
     * Override this method to select only the attributes needed for frontend display.
     * @return AssetQuery
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at'])));
    }

    /**
     * Override this method to select only the attributes needed for XML sitemap generation.
     * @return $this
     */
    public function selectSitemapAttributes()
    {
        return $this->selectSiteAttributes();
    }

    /**
     * @return AssetQuery
     */
    public function withFiles()
    {
        return $this->with([
            'file' => function (FileQuery $query) {
                $query->selectSiteAttributes()
                    ->replaceI18nAttributes()
                    ->withFolder();
            }
        ]);
    }

    /**
     * @return AssetQuery
     */
    public function withoutSections()
    {
        return $this->andWhere(['section_id' => null]);
    }
}