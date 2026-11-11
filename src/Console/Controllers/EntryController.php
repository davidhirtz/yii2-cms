<?php

declare(strict_types=1);

namespace Hirtz\Cms\Console\Controllers;

use Hirtz\Cms\Models\Actions\SyncEntryReferences;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Maintains entries.
 */
class EntryController extends Controller
{
    use ControllerTrait;
    use ModuleTrait;

    /**
     * Rebuilds the entry references of every registered record, deletes those whose record is gone and recounts the relations.
     *
     * Needed once for records saved before the index existed and after writes outside `save()`.
     *
     * @noinspection PhpUnused
     */
    public function actionReferences(): int
    {
        foreach (static::getModule()->getEntryReferenceModelClasses() as $class) {
            $count = 0;

            foreach (Yii::createObject($class)::find()->each() as $record) {
                if ($record instanceof CustomAttributeInterface) {
                    SyncEntryReferences::create($record);
                    $count++;
                }
            }

            $this->stdout("$class: $count records" . PHP_EOL);
        }

        $deletedCount = $this->deleteOrphanedReferences();
        $this->stdout("$deletedCount references of deleted records were removed" . PHP_EOL, Console::FG_GREEN);

        $updatedCount = 0;

        foreach (Entry::find()->each() as $entry) {
            $updatedCount += $entry->updateRelationCount();
        }

        $this->stdout("$updatedCount relation counts were corrected" . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    protected function deleteOrphanedReferences(): int
    {
        $classes = EntryReference::find()->select('model_class')->distinct()->column();
        $entryIds = [];
        $deletedCount = 0;

        foreach ($classes as $class) {
            $ids = array_map(intval(...), EntryReference::find()
                ->select('model_id')
                ->where(['model_class' => $class])
                ->distinct()
                ->column());

            $existing = class_exists($class) && is_subclass_of($class, ActiveRecord::class)
                ? array_map(intval(...), Yii::createObject($class)::find()->select('id')->andWhere(['id' => $ids])->column())
                : [];

            $orphans = array_values(array_diff($ids, $existing));

            if (!$orphans) {
                continue;
            }

            foreach (EntryReference::find()->where(['model_class' => $class, 'model_id' => $orphans])->all() as $reference) {
                $reference->setIsBatch(true);
                $reference->delete();

                $entryIds[] = $reference->entry_id;
                $deletedCount++;
            }
        }

        foreach (Entry::find()->where(['id' => array_values(array_unique($entryIds))])->all() as $entry) {
            $entry->updateReferenceCount();
        }

        return $deletedCount;
    }
}
