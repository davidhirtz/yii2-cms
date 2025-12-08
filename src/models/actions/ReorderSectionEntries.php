<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Skeleton\Models\Trail;
use Yii;

/**
 * @template T of SectionEntry
 * @extends ReorderActiveRecords<T>
 */
class ReorderSectionEntries extends ReorderActiveRecords
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

    #[\Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->section, Yii::t('cms', 'Linked entry order changed'));
        parent::afterReorder();
    }
}
