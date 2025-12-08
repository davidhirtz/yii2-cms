<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\actions;

use Hirtz\Cms\models\Asset;
use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\models\Trail;
use Yii;

/**
 * @extends ReorderActiveRecords<Asset>
 */
class ReorderAssets extends ReorderActiveRecords
{
    public function __construct(protected Entry|Section $parent, array $assetIds = [])
    {
        $assets = $parent->getAssets()
            ->select(['id', 'position'])
            ->andWhere(['id' => $assetIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($assetIds);

        parent::__construct($assets, $order);
    }

    #[\Override]
    protected function afterReorder(): void
    {
        $trail = Trail::createOrderTrail($this->parent, Yii::t('cms', 'Asset order changed'));

        $this->parent->updated_at = new DateTime();
        $this->parent->update();

        if ($this->parent instanceof Section) {
            $entry = $this->parent->entry;
            Trail::createOrderTrail($entry, Yii::t('cms', 'Section asset order changed'), [
                'trail_id' => $trail->id,
            ]);

            $entry->updated_at = new DateTime();
            $entry->update();
        }

        parent::afterReorder();
    }
}
