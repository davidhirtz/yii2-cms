<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * @extends ModelHeader<Entry|null>
 */
class EntryHeader extends ModelHeader
{
    /**
     * @use ProviderTrait<EntryActiveDataProvider|null>
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
        }

        if ($this->provider) {
            $type = Entry::instance()::findType($this->provider->type);

            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= $type?->getPlural() ?: Yii::t('cms', 'COMMON_ENTRIES');
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
