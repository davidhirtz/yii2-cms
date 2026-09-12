<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @see CategoryController::actionDelete()
 *
 * @extends DeleteButton<Category>
 */
class CategoryDeleteButton extends DeleteButton
{
    #[\Override]
    public function isVisible(): bool
    {
        return parent::isVisible()
            && $this->webuser->can(Category::AUTH_CATEGORY_DELETE, ['category' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model->getBranchCount()) {
            $this->title ??= Yii::t('cms', 'CATEGORY_DELETE_TITLE');
            $this->message ??= Yii::t('cms', 'CATEGORY_DELETE_WARNING');
        }

        $this->url ??= ['/admin/cms/category/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
