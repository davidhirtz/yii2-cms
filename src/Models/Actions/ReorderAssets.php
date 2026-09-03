<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Trail;
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
        $trail = Trail::createOrderTrail($this->parent, Lang::t('cms', 'REORDER_ASSETS_ASSET_ORDER_CHANGED'));

        $this->parent->updated_at = new DateTime();
        $this->parent->update();

        if ($this->parent instanceof Section) {
            $entry = $this->parent->entry;
            Trail::createOrderTrail($entry, Lang::t('cms', 'REORDER_ASSETS_SECTION_ASSET_ORDER_CHANGED'), [
                'trail_id' => $trail->id,
            ]);

            $entry->updated_at = new DateTime();
            $entry->update();
        }

        parent::afterReorder();
    }
}
