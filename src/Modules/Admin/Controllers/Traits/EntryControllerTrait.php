<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Entry;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait EntryControllerTrait
{
    protected function findEntry(int $id, ?string $permissionName = null): Entry
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !$this->webuser->can($permissionName, ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        return $entry;
    }
}
