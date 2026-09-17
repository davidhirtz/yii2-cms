<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Override;

/**
 * @extends ModelHeader<Section>
 */
class SectionHeader extends ModelHeader
{
    #[Override]
    protected function configure(): void
    {
        $this->subheading ??= FrontendLink::make()->model($this->model)->addClass('hidden-sticky');

        parent::configure();
    }
}
