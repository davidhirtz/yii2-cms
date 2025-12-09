<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\models\queries\FileQuery;
use Hirtz\Skeleton\Db\I18nActiveQuery;

/**
 * @extends I18nActiveQuery<Asset>
 */
class AssetQuery extends I18nActiveQuery
{
    /**
     * Override this method to select only the attributes needed for frontend display.
     */
    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(), [
            'updated_by_user_id',
            'created_at',
        ])));
    }

    /**
     * Override this method to select only the attributes needed for XML sitemap generation.
     */
    public function selectSitemapAttributes(): static
    {
        return $this->selectSiteAttributes();
    }

    public function withFiles(): static
    {
        return $this->with([
            'file' => function (FileQuery $query): void {
                $query->selectSiteAttributes()
                    ->replaceI18nAttributes();
            }
        ]);
    }

    public function withoutSections(): static
    {
        return $this->andWhere(['section_id' => null]);
    }
}
