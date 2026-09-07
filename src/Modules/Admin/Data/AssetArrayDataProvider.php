<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\CategoryQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use yii\data\ArrayDataProvider;

/**
 * @property CategoryQuery $query
 * @property Category[] $models
 * @method Category[] getModels()
 */
class AssetArrayDataProvider extends ArrayDataProvider
{
    public Entry|Section $parent;

    public function init(): void
    {
        $query = $this->parent instanceof Entry
            ? $this->parent->getAssets()->withoutSections()
            : $this->parent->getAssets();

        $this->allModels = $query->withFiles()->all();

        parent::init();
    }
}
