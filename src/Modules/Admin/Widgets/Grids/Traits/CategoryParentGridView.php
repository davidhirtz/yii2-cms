<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\CategoryGridView;
use Override;

class CategoryParentGridView extends CategoryGridView
{
    #[Override]
    protected function configure(): void
    {
        $this->layout = '{items}';

        $this->header = [];
        $this->footer = [];

        parent::configure();
    }
}
