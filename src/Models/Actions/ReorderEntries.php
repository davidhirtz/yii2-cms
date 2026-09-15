<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;

/**
 * @extends ReorderActiveRecords<Entry>
 */
class ReorderEntries extends ReorderActiveRecords
{
    /**
     * @param list<int> $entryIds
     */
    public function __construct(protected ?Entry $parent, array $entryIds)
    {
        $entries = ($parent?->findChildren() ?? Entry::find())
            ->select(['id', 'position'])
            ->andWhere(['id' => $entryIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($entryIds);

        parent::__construct($entries, $order);
    }

    #[\Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->parent, Message::make('cms', 'COMMON_ENTRY_ORDER_CHANGED'));

        if ($this->parent) {
            $this->parent->updated_at = new DateTime();
            $this->parent->update();
        }

        parent::afterReorder();
    }
}
