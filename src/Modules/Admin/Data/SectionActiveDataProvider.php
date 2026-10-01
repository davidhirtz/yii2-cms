<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\BlockQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Queries\AssetQuery;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Override;

/**
 * @property SectionQuery<Section>|null $query
 * @extends ActiveDataProvider<Section>
 */
class SectionActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    public Entry $entry;

    #[Override]
    public function init(): void
    {
        $this->setSort(false);
        $this->setPagination(false);

        parent::init();
    }

    #[Override]
    protected function prepareQuery(): void
    {
        $this->query ??= $this->entry->getSections();

        // what the grid previews a section by
        $withAssets = fn (AssetQuery $query) => $query->withFiles();

        $this->query->with(['assets' => $withAssets]);

        if (static::getModule()->enableBlocks) {
            $this->query->with([
                'block' => fn (BlockQuery $query) => $query->with(['assets' => $withAssets]),
            ]);
        }

        parent::prepareQuery();
    }
}
