<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\cms\modules\admin\widgets\grids\CategoryGridView;
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
