<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class CategoryHeader extends Header
{
    /**
     * @use ModelTrait<Category>
     */
    use ModelTrait;

    /**
     * @use ProviderTrait<CategoryActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->model ??= $this->provider?->category;

        if ($this->model) {
            $this->title ??= $this->model->getOldAttribute($this->model->getI18nAttributeName('name'));
            $this->subheading ??= FrontendLink::make()->model($this->model)->addClass('hidden-sticky');
            $this->url ??= $this->model->getAdminRoute();

            $this->addCategoryBreadcrumbs($this->model);
            $this->addContent($this->getCategoryActionDropdown());
        }

        if ($this->provider) {
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= Lang::t('cms', 'COMMON_CATEGORIES');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider->type];
        }

        if (!$this->model) {
            $this->addContent($this->getCreateCategoryButton());
        }

        parent::configure();
    }

    protected function addCategoryBreadcrumbs(Category $category): void
    {
        $this->addBreadcrumb(Yii::t('cms', 'COMMON_CATEGORIES'), ['/admin/cms/category/index']);

        if ($category->parent_id) {
            $isIndex = Yii::$app->requestedRoute === 'admin/cms/category/index';

            foreach ($category->ancestors as $ancestor) {
                $this->addBreadcrumb($ancestor->getI18nAttribute('name'), $isIndex
                    ? ['index', 'parent' => $ancestor->id]
                    : $ancestor->getAdminRoute());
            }
        }
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Lang::t('cms', 'CATEGORY_HEADER_CREATE_CATEGORY'))
            ->icon('plus')
            ->url(['/admin/cms/category/create', 'parent' => $this->provider->category?->id]);
    }

    protected function getCategoryActionDropdown(): ?Stringable
    {
        return CategoryActionDropdown::make()->model($this->model);
    }
}
