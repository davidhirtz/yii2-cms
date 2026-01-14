<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder;
use yii\base\Event;

/**
 * @property EntrySiteRelationsBuilder $sender
 */
class EntrySiteRelationsBuilderEvent extends Event
{
}
