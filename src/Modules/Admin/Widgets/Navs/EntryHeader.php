<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class EntryHeader extends Header
{
    /**
     * @use ModelTrait<Entry|Section>
     */
    use ModelTrait;
    use ModuleTrait;

    /**
     * @use ProviderTrait<EntryActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->model ??= $this->provider?->parent;

        if (!$this->provider || $this->model) {
            $this->breadcrumbs ??= [
                new Breadcrumb(Yii::t('app', 'Entries'), [
                    '/admin/cms/entry/index',
                    'type' => static::getModule()->defaultEntryType,
                ]),
            ];
        }

        if ($this->model) {
            $entry = $this->model instanceof Section ? $this->model->entry : $this->model;

            $this->title ??= $entry->getOldAttribute($entry->getI18nAttributeName('name'));
            $this->subheading ??= FrontendLink::make()->model($this->model);
            $this->url ??= $entry->getAdminRoute();

            if ($this->model instanceof Section) {
                $this->subtitle ??= Yii::t('skeleton', '{model} #{id}', [
                    'model' => $this->model->getTypeName(),
                    'id' => $this->model->id,
                ]);
            }

            $this->addEntryBreadcrumbs($entry);
        }

        if ($this->provider) {
            $typeOptions = Entry::instance()::getTypes()[$this->provider->type] ?? null;

            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= $typeOptions['plural'] ?? $typeOptions['name'] ?? Yii::t('cms', 'Entries');
            $this->url ??= ['/admin/cms/entry/index', 'type' => $this->provider?->type];

            $this->addContent($this->getCreateEntryButton());
        }

        parent::configure();
    }

    protected function addEntryBreadcrumbs(Entry $entry): void
    {
        if ($entry->parent_id) {
            $isIndex = Yii::$app->requestedRoute === 'admin/cms/entry/index';

            foreach ($entry->ancestors as $ancestor) {
                $this->addBreadcrumb($ancestor->getI18nAttribute('name'), $isIndex
                    ? ['index', 'parent' => $ancestor->id]
                    : $ancestor->getAdminRoute());
            }
        }
    }

    protected function getCreateEntryButton(): ?Stringable
    {
        return EntryCreateButton::make();
    }
}
