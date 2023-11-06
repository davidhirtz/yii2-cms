<?php

namespace davidhirtz\yii2\cms\models\actions;

use DateTime;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

class ReorderSectionsAction extends ReorderActiveRecords
{
    public function __construct(protected Entry $entry, array $sectionIds = [])
    {
        $sections = $entry->getSections()
            ->select(['id', 'position'])
            ->andWhere(['id' => $sectionIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($sectionIds);

        parent::__construct($sections, $order);
    }

    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->entry, Yii::t('cms', 'Section order changed'));

        $this->entry->updated_at = new DateTime();
        $this->entry->update();

        parent::afterReorder();
    }
}