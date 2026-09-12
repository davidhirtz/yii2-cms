<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\Traits\EntryHeaderTrait;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class EntryHeader extends Header
{
    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;
    use EntryHeaderTrait;

    /**
     * @use ProviderTrait<EntryActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->model ??= $this->provider?->parent;

        if ($this->model) {
            $this->title ??= $this->model->getOldAttribute($this->model->getI18nAttributeName('name'));
            $this->subheading ??= FrontendLink::make()->model($this->model)->addClass('hidden-sticky');
            $this->url ??= $this->model->getAdminRoute();

            $this->addEntryBreadcrumbs($this->model);
        }

        if ($this->provider) {
            $typeOptions = $this->provider->type ? Entry::instance()::getTypes()[$this->provider->type] ?? null : null;

            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= $typeOptions['plural'] ?? $typeOptions['name'] ?? Yii::t('cms', 'COMMON_ENTRIES');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider->type];

            $this->addContent($this->getCreateEntryButton());
        }

        parent::configure();
    }

    protected function getCreateEntryButton(): ?Stringable
    {
        return EntryCreateButton::make();
    }
}
