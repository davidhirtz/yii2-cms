<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

class ReorderSectionEntriesAction extends ReorderActiveRecordsAction
{
    public function __construct(protected Section $section, array $folderIds)
    {
        $sectionEntries = $section->getSectionEntries()
            ->andWhere(['id' => $folderIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($folderIds);

        parent::__construct($sectionEntries, $order);
    }

    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->section, Yii::t('cms', 'Linked entry order changed'));
        parent::afterReorder();
    }
}