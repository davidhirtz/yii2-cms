<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations;
use yii\base\Event;

/**
 * @property PreloadEntrySiteRelations $sender
 */
class EntrySiteRelationsEvent extends Event
{
}
