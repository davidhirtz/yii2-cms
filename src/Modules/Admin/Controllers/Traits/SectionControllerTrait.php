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
     * Whether the controller works with this section at all, the counterpart of
     * {@see EntryControllerTrait::isEntryAllowed()}. A section the record *already has* is not refused here — only
     * listing and adding are, which is the exemption {@see \Hirtz\Skeleton\Models\Types\Type::isAvailableOrStored()}
     * makes for a stored value the configuration no longer allows.
     */
    protected function isSectionAllowed(Section $section): bool
    {
        return true;
    }
}
