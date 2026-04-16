<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * @property CategoryActiveDataProvider $provider
 */
class CategoryHeader extends Header
{
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
            $this->title ??= $this->provider->category?->getI18nAttribute('name') ?? Yii::t('cms', 'Entries');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider?->type];

            $this->addContent($this->getCreateCategoryButton());

        $this->subtitle ??= $this->getPaginationSubtitle($this->provider);

        parent::configure();
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('cms', 'Create Category'))
            ->icon('plus')
            ->url(['/admin/cms/entry/create', 'parent' => $this->provider->category?->id]);
    }
}
