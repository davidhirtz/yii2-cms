<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Entry;
use yii\web\NotFoundHttpException;

trait EntryControllerTrait
{
    protected function findEntry(int $id): Entry
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }
}
