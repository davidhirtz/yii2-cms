<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

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
            $this->getCopyButton(),
            $this->getCloneButton(),
            $this->getLinkButton(),
        ]);
    }

    /**
     * @return string
     */
    protected function getCopyButton()
    {
        return Html::a(Html::iconText('copy', Yii::t('cms', 'Move / Copy')), ['entries', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
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