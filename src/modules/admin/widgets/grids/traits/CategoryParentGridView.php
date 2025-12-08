<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids\traits;

use Hirtz\Cms\modules\admin\widgets\grids\CategoryGridView;
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
