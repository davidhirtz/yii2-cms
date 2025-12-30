<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Html\Button;
use Stringable;
use Yii;

/**
 * @template T of Category
 * @extends AbstractPanel<T>
 */
class CategoryPanel extends AbstractPanel
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
