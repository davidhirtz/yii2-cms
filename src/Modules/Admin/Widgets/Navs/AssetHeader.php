<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AssetHeader extends Widget
{
    /**
     * @use ModelTrait<Entry|Section>
     */
    use ModelTrait;
    use TagContentTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $header = $this->model instanceof Section
            ? SectionHeader::make()->model($this->model)
            : EntryHeader::make()->model($this->model);

        return $header->content(...$this->content);
    }
}
