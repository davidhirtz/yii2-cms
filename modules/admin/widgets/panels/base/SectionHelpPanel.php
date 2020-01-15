<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Section;

/**
 * Class SectionHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\SectionHelpPanel
 *
 * @property Section $model
 */
class SectionHelpPanel extends HelpPanel
{
    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getLinkButton(),
            $this->getCloneButton(),
        ]);
    }

    /**
     * @return bool
     */
    protected function isDraft(): bool
    {
        return $this->model->isDraft() || $this->model->entry->isDraft();
    }
}