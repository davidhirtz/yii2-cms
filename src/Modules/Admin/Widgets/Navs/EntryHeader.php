<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * @property Entry|null $entry
 * @property EntryActiveDataProvider|null $provider
 */
class EntryHeader extends Header
{
    use ModelTrait;
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        if (!$this->model) {
            $typeOptions = $this->provider ? Entry::instance()::getTypes()[$this->provider->type] ?? null : null;
            $this->title ??= $typeOptions['plural'] ?? $typeOptions['name'] ?? Yii::t('cms', 'Entries');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider?->type];

            $this->addContent($this->getCreateEntryButton());
        }

        $this->subtitle ??= $this->getPaginationSubtitle($this->provider);

        parent::configure();
    }

    protected function getCreateEntryButton(): ?Stringable
    {
        return EntryCreateButton::make();
    }
}
