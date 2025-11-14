<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
use Yii;

/**
 * @property Category $model
 */
class CategoryHelpPanel extends HelpPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getCreateCategoryButton(),
            $this->getEntryGridViewButton(),
            $this->getLinkButton(),
        ]);
    }

    protected function getEntryGridViewButton(): ?Stringable
    {
        return $this->model->hasEntriesEnabled()
            ? Button::make()
                ->primary()
                ->text(Yii::t('cms', 'View All Entries'))
                ->icon('book')
                ->href(['entry/index', 'category' => $this->model->id])
            : null;
    }

    protected function getCreateCategoryButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'New Category'))
            ->icon('plus')
            ->href(['category/create', 'id' => $this->model->id]);
    }
}
