<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Entry;

/**
 * Class EntryHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryHelpPanel
 *
 * @property Entry $model
 */
class EntryHelpPanel extends HelpPanel
{
    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getCloneButton(),
            $this->getLinkButton(),
        ]);
    }
}