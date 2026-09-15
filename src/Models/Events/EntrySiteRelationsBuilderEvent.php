<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder;
use Hirtz\Cms\Models\Entry;
use yii\base\Event;

/**
 * @property EntrySiteRelationsBuilder<Entry> $sender
 */
class EntrySiteRelationsBuilderEvent extends Event
{
}
