<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
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

    protected function getCopyButton(): ?Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'Move / Copy'))
            ->icon('copy')
            ->href(['entries', 'id' => $this->model->id]);
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft() || $this->model->entry->isDraft();
    }
}
