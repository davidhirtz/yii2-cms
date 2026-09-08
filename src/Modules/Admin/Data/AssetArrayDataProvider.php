<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use yii\data\ArrayDataProvider;

/**
 * @property Asset[] $models
 * @method Asset[] getModels()
 */
class AssetArrayDataProvider extends ArrayDataProvider
{
    public Entry|Section $parent;

    #[\Override]
    public function init(): void
    {
        $query = $this->parent instanceof Entry
            ? $this->parent->getAssets()->withoutSections()
            : $this->parent->getAssets();

        $this->allModels = $query->withFiles()->all();

        parent::init();
    }
}
