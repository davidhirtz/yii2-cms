<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\Button;
use Stringable;
use Yii;

/**
 * @property Section $model
 */
class SectionHelpPanel extends AbstractPanel
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
