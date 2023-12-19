<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * @property Section $model
 */
class SectionHelpPanel extends HelpPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getCopyButton(),
            $this->getDuplicateButton(),
            $this->getLinkButton(),
        ]);
    }

    protected function getCopyButton(): string
    {
        return Html::a(Html::iconText('copy', Yii::t('cms', 'Move / Copy')), ['entries', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
        ]);
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft() || $this->model->entry->isDraft();
    }
}
