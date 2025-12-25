<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\CategoryGridView;
use Override;

class CategoryParentGridView extends CategoryGridView
{
    #[Override]
    public function configure(): void
    {
        $this->layout = '{items}';

        $this->header = [];
        $this->footer = [];

        parent::configure();
    }
}
