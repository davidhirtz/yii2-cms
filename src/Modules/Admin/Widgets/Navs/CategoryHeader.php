<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class CategoryHeader extends Header
{
    /**
     * @use ProviderTrait<CategoryActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->title ??= $this->provider->category?->getI18nAttribute('name') ?? Lang::t('cms', 'COMMON_CATEGORIES');
        $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider?->type];

        if ($this->provider) {
            $this->addContent($this->getCreateCategoryButton());
        }

        $this->subtitle ??= $this->getPaginationSubtitle($this->provider);

        parent::configure();
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Lang::t('cms', 'CATEGORY_HEADER_CREATE_CATEGORY'))
            ->icon('plus')
            ->url(['/admin/cms/category/create', 'parent' => $this->provider->category?->id]);
    }
}
