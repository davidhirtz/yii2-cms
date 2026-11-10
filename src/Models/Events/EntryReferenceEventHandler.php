<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Models\Actions\SyncEntryReferences;
use Hirtz\Cms\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use yii\db\AfterSaveEvent;

/**
 * Registered on every record, so it returns at once unless a link or HTML custom attribute is there and, on update,
 * changed.
 */
class EntryReferenceEventHandler
{
    public static function handleAfterSave(ActiveRecord $record, AfterSaveEvent $event): void
    {
        if (!$record instanceof CustomAttributeInterface) {
            return;
        }

        foreach (array_keys($event->changedAttributes) as $name) {
            $definition = $record->getCustomAttribute((string)$name);

            if ($definition !== null && static::holdsLinks($definition)) {
                SyncEntryReferences::create($record);
                return;
            }
        }
    }

    public static function handleAfterDelete(ActiveRecord $record): void
    {
        if (!$record instanceof CustomAttributeInterface) {
            return;
        }

        foreach ($record->getCustomAttributeDefinitions() as $definition) {
            if (static::holdsLinks($definition)) {
                SyncEntryReferences::create($record);
                return;
            }
        }
    }

    public static function holdsLinks(CustomAttribute $definition): bool
    {
        if ($definition instanceof HtmlCustomAttribute) {
            return true;
        }

        if ($definition instanceof LinkCustomAttribute) {
            return $definition->allowsEntries();
        }

        if ($definition instanceof GroupCustomAttribute) {
            foreach ($definition->getAttributes() as $attribute) {
                if (static::holdsLinks($attribute)) {
                    return true;
                }
            }
        }

        return false;
    }
}
