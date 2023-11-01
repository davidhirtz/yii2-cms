<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Entry;

/**
 * @property Entry $model
 */
class EntryHelpPanel extends HelpPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getCloneButton(),
            $this->getLinkButton(),
        ]);
    }
}