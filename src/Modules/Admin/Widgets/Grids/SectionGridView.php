<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\SectionEntryCountColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\CreateButton;
use Hirtz\Skeleton\Widgets\Grids\Traits\StatusGridViewTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\helpers\StringHelper;

/**
 * @extends GridView<Section>
 * @property SectionActiveDataProvider $provider
 */
class SectionGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    public bool $showDeleteButton = false;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'section-grid-view';
        $this->model ??= Section::instance();
        $this->orderRoute = ['order', 'entry' => $this->provider->entry->id];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getEntriesCountColumn(),
            $this->getAssetCountColumn(),
            $this->getButtonColumn(),
        ];

        $this->footer ??= [
            $this->getCreateSectionButton(),
        ];

        parent::configure();
    }

    protected function getCreateSectionButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_CREATE, ['entry' => $this->provider->entry])) {
            return null;
        }

        return CreateButton::make()
            ->text(Yii::t('cms', 'New Section'))
            ->href(['/admin/section/create', 'entry' => $this->provider->entry->id]);
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Section $section): Stringable|string
    {
        $html = $section->getNameColumnContent();
        $cssClass = null;

        if (!$html) {
            $name = $section->getI18nAttribute('name');
            $html = $name ? Div::make()->class('strong')->text($name) : null;
        }

        if (!$html) {
            foreach ($section->assets as $asset) {
                if ($asset->file->hasPreview()) {
                    $html = Thumbnail::make()->file($asset->file);
                    break;
                }
            }
        }

        if (!$html) {
            $html = $section->getI18nAttribute('content') ?? '';
            $html = 'html' === $section->contentType ? strip_tags((string) $html) : $html;
            $html = StringHelper::truncate($html, 100);
        }

        if (!$html) {
            $html = Yii::t('cms', '[ No title ]');
            $cssClass = 'text-muted';
        }

        return A::make()
            ->content($html)
            ->href($this->getRoute($section))
            ->class($cssClass);
    }

    protected function getAssetCountColumn(): ?Column
    {
        return AssetCountColumn::make();
    }

    protected function getEntriesCountColumn(): ?Column
    {
        return SectionEntryCountColumn::make();
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Section $section): array
    {
        $buttons = [];

        if (
            $this->isSortable()
            && $this->provider->getCount() > 1
            && $this->webuser->can(Section::AUTH_SECTION_ORDER)
        ) {
            $buttons[] = DraggableSortGridButton::make();
        }

        if ($this->webuser->can(Section::AUTH_SECTION_UPDATE, ['section' => $section])) {
            $buttons[] = ViewGridButton::make()
                ->model($section);
        }

        if ($this->showDeleteButton && $this->webuser->can(Section::AUTH_SECTION_DELETE, ['section' => $section])) {
            $buttons[] = DeleteGridButton::make()
                ->model($section);
        }

        return $buttons;
    }
}
