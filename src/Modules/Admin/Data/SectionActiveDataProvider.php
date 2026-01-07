<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Override;

/**
 * @property SectionQuery|null $query
 * @extends ActiveDataProvider<Section>
 */
class SectionActiveDataProvider extends ActiveDataProvider
{
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
        parent::prepareQuery();
    }
}
