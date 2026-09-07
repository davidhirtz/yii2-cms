<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AssetSubmenu extends Widget
{
    /**
     * @use ModelTrait<Entry|Section>
     */
    use ModelTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->model instanceof Section
            ? SectionSubmenu::make()->model($this->model)
            : EntrySubmenu::make()->model($this->model);
    }
}
