<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\CategoryDeleteButton;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\LinkButtonTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use Yii;

class CategoryActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Category>
     */
    use ModelTrait;
    use LinkButtonTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getCreateCategoryButton(),
            $this->getEntryGridViewButton(),
            $this->getLinkButton(),
            $this->getDeleteButton(),
        );

        parent::configure();
    }

    protected function getCreateCategoryButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'CATEGORY_NEW_CATEGORY'))
            ->icon('plus')
            ->url(['/admin/cms/category/create', 'parent' => $this->model->id]);
    }

    protected function getEntryGridViewButton(): ?Stringable
    {
        return $this->model->hasEntriesEnabled()
            ? Button::make()
                ->primary()
                ->text(Yii::t('cms', 'CATEGORY_VIEW_ALL_ENTRIES'))
                ->icon('book')
                ->url(['/admin/cms/entry/index', 'category' => $this->model->id])
            : null;
    }

    protected function getDeleteButton(): ?Stringable
    {
        return CategoryDeleteButton::make()
            ->model($this->model);
    }
}
