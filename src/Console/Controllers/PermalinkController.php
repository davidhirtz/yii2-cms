<?php

declare(strict_types=1);

namespace Hirtz\Cms\Console\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Rebuilds permalink records.
 *
 * A {@see Permalink} is written when an entry is saved, so anything that changes one outside a save leaves it
 * stale — adding a language, for instance. Those are one-off jobs, not runtime cascades, which is what this
 * command is for.
 */
class PermalinkController extends Controller
{
    use ControllerTrait;
    use ModuleTrait;

    /**
     * Rewrites the permalinks of every entry.
     *
     * Inserts the missing ones and deletes the ones whose entry no longer has a URL.
     */
    public function actionRebuild(): void
    {
        $count = 0;

        foreach (Entry::find()->orderBy(['id' => SORT_ASC])->each() as $entry) {
            $entry->savePermalinks();
            $count++;
        }

        if ($count) {
            $this->stdout("Rebuilt permalinks for $count records.\n", Console::FG_GREEN);
        }
    }
}
