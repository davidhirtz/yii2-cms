<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Widgets\Buttons\Button;
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
                ->text(Lang::t('cms', 'CATEGORY_VIEW_ALL_ENTRIES'))
                ->icon('book')
                ->url(['entry/index', 'category' => $this->model->id])
            : null;
    }

    protected function getCreateCategoryButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->text(Lang::t('cms', 'CATEGORY_NEW_CATEGORY'))
            ->icon('plus')
            ->url(['category/create', 'id' => $this->model->id]);
    }
}
