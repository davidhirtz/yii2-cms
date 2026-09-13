<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Section;
use yii\web\NotFoundHttpException;

trait SectionControllerTrait
{
    protected function findSection(int $id): Section
    {
        if (!$section = Section::findOne($id)) {
            throw new NotFoundHttpException();
        }

        return $section;
    }
}
