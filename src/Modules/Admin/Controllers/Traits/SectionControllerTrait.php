<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Section;
use yii\web\NotFoundHttpException;

trait SectionControllerTrait
{
    protected function findSection(int $id): Section
    {
        $section = Section::findOne($id);

        if (!$section || !$this->isSectionAllowed($section)) {
            throw new NotFoundHttpException();
        }

        return $section;
    }

    /**
     * A section the record already has is not refused, only listing and adding are, the exemption of
     * {@see \Hirtz\Skeleton\Models\Types\Type::isAvailableOrStored()}.
     */
    protected function isSectionAllowed(Section $section): bool
    {
        return true;
    }
}
