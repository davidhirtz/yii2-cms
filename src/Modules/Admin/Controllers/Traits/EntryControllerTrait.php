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
     * Whether the controller works with this entry at all. A controller behind a submenu tab answers with the
     * capability that tab is shown for, so a route cannot do what the admin does not offer — the media
     * `Modules\Admin\Controllers\Traits\AssetControllerTrait` refuses an asset model the same way.
     */
    protected function isEntryAllowed(Entry $entry): bool
    {
        return true;
    }
}
