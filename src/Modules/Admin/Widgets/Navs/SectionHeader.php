<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\Traits\EntryHeaderTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;

class SectionHeader extends Header
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;
    use EntryHeaderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->title ??= $this->model->entry->getI18nAttribute('name');
        $this->subheading ??= FrontendLink::make()->model($this->model)->addClass('hidden-sticky');
        $this->url ??= $this->model->entry->getAdminRoute();

        $this->subtitle ??= Lang::t('skeleton', 'COMMON_MODEL_ID', [
            'model' => $this->model->getTypeName(),
            'id' => $this->model->id,
        ]);

        $this->addEntryBreadcrumbs($this->model->entry);
        $this->addSectionBreadcrumbs();

        parent::configure();
    }

    protected function addSectionBreadcrumbs(): void
    {
        $this->addBreadcrumb(
            $this->model->entry->getI18nAttribute('name'),
            $this->model->entry->getAdminRoute(),
        );

        $this->addBreadcrumb(Lang::t('cms', 'COMMON_SECTIONS'), [
            '/admin/cms/section/index',
            'entry' => $this->model->entry_id,
        ]);
    }
}
