<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

/**
 * @extends ReorderActiveRecords<Entry>
 */
class ReorderEntries extends ReorderActiveRecords
{
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

    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->parent, Yii::t('cms', 'Entry order changed'));

        if ($this->parent) {
            $this->parent->updated_at = new DateTime();
            $this->parent->update();
        }

        parent::afterReorder();
    }
}
