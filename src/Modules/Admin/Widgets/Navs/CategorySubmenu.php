<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class CategorySubmenu extends Submenu
{
    /**
     * @use ModelTrait<Category>
     */
    use ModelTrait;

    protected array $additionalActiveRoutes = [];

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getCategoryUpdateItem(),
            $this->getSubcategoriesItem(),
        );

        parent::configure();
    }

    protected function getCategoryUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label(Yii::t('skeleton', 'COMMON_GENERAL'))
            ->routes(['admin/cms/category/update', ...$this->additionalActiveRoutes['category'] ?? []])
            ->url(['/admin/cms/category/update', 'id' => $this->model->id]);
    }

    protected function getSubcategoriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->getBranchCount())
            ->icon('folder-open')
            ->label(Yii::t('cms', 'COMMON_SUBCATEGORIES'))
            ->routes(['admin/cms/category/index', ...$this->additionalActiveRoutes['subcategories'] ?? []])
            ->url(['/admin/cms/category/index', 'parent' => $this->model->id]);
    }
}
