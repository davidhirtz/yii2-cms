<?php

declare(strict_types=1);

namespace Hirtz\Cms\Sitemap;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Queries\CategoryQuery;
use Override;
use Yii;

/**
 * @extends RecordSitemap<Category>
 */
class CategorySitemap extends RecordSitemap
{
    public string $modelClass = Category::class;

    #[Override]
    protected function getQuery(): CategoryQuery
    {
        return Category::find()
            ->selectSitemapAttributes()
            ->enabled()
            ->withTranslations(Yii::$app->getI18n()->getLanguages())
            ->orderBy(['id' => SORT_ASC]);
    }
}
