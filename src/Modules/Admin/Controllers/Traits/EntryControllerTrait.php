<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Entry;
use yii\web\NotFoundHttpException;

trait EntryControllerTrait
{
    protected function findEntry(int $id): Entry
    {
        $entry = Entry::findOne($id);

        if (!$entry || !$this->isEntryAllowed($entry)) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }

    /**
     * A controller behind a submenu tab answers with the capability the tab is shown for, as media's
     * `AssetControllerTrait` does.
     */
    protected function isEntryAllowed(Entry $entry): bool
    {
        return true;
    }
}
