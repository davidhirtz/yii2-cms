<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * @extends ModelHeader<Category|null>
 */
class CategoryHeader extends ModelHeader
{
    /**
     * @use ProviderTrait<CategoryActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->model ??= $this->provider?->parent;

        if ($this->model) {
            // The fallback reads the translation through the getter, which loads it for `getOldAttribute()`.
            $this->title ??= $this->model->getOldAttribute($this->model->getI18nAttributeName('name', fallback: true));
            $this->subheading ??= FrontendLink::make()->model($this->model)->addClass('hidden-sticky');

            $this->addContent($this->getCategoryActionDropdown());
        }

        if ($this->provider) {
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= Yii::t('cms', 'COMMON_CATEGORIES');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider->type];
        }

        if (!$this->model) {
            $this->addContent($this->getCreateCategoryButton());
        }

        parent::configure();
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('cms', 'CATEGORY_CREATE_BUTTON'))
            ->icon('plus')
            ->url([
                '/admin/cms/category/create',
                'parent' => $this->provider?->parent?->id,
                'type' => $this->provider?->type,
            ]);
    }

    protected function getCategoryActionDropdown(): ?Stringable
    {
        return CategoryActionDropdown::make()->model($this->model);
    }
}
