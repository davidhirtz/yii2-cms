<?php

declare(strict_types=1);

namespace Hirtz\Cms\Console\Controllers;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Rebuilds {@see Permalink} records.
 *
 * Permalinks are written when a model is saved, so anything that changes them outside a save leaves them stale:
 * adding a language, or turning `Module::$enableCategoryUrls` on or off. Those are one-off jobs, not runtime
 * cascades, which is what this command is for.
 */
class PermalinkController extends Controller
{
    use ControllerTrait;
    use ModuleTrait;

    /**
     * Rewrites the permalinks of every entry and category, inserting the missing ones and deleting the ones whose
     * model no longer has a URL.
     */
    public function actionRebuild(): void
    {
        $this->rebuildModels(Entry::find()->orderBy(['id' => SORT_ASC])->each());

        if (static::getModule()->enableCategories) {
            // Ordered by `lft` so a parent is always written before the children that derive their prefix from it.
            $this->rebuildModels(Category::find()->orderBy(['lft' => SORT_ASC])->each());
        }
    }

    /**
     * @param iterable<PermalinkInterface> $models
     */
    protected function rebuildModels(iterable $models): void
    {
        $count = 0;

        foreach ($models as $model) {
            $model->savePermalinks();
            $count++;
        }

        if ($count) {
            $this->stdout("Rebuilt permalinks for $count records.\n", Console::FG_GREEN);
        }
    }

    /**
     * Removes permalinks whose model no longer exists. Nothing should produce these — it is a repair for data that
     * was changed outside the models.
     */
    public function actionPrune(): void
    {
        $deleted = 0;

        foreach ([Entry::class, Category::class] as $class) {
            $ids = Yii::createObject($class)::find()
                ->select('id')
                ->column();

            $permalinks = Permalink::find()
                ->andWhere(['model' => $class])
                ->andWhere(['not in', 'model_id', $ids])
                ->all();

            foreach ($permalinks as $permalink) {
                $permalink->delete();
                $deleted++;
            }
        }

        $this->stdout("Deleted $deleted orphaned permalinks.\n", Console::FG_GREEN);
    }
}
