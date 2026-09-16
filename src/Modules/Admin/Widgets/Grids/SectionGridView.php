<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\SectionEntryCountColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\CheckboxColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridFooter;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Modal;
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

    public bool $showDeleteButton = false;
    public bool $showSelection = true;

    protected string $layout = '{summary}{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'section-grid-view';
        $this->orderRoute = ['order', 'entry' => $this->provider->entry->id];

        $this->showSelection = $this->showSelection
            && $this->provider->getCount() > 1
            && $this->webuser->can(Entry::AUTH_ENTRY);

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getContentColumn(),
            $this->getEntriesCountColumn(),
            $this->getAssetCountColumn(),
            $this->getButtonColumn(),
        ];

        if ($this->showSelection) {
            $this->footer ??= GridFooter::make()
                ->attributes($this->footerAttributes)
                ->addClass('hidden flex-has-selection')
                ->content($this->getSelectionButton());
        }

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'SECTION_GRID_SUMMARY_EMPTY'))
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    protected function getCheckboxColumn(): ?Column
    {
        return $this->showSelection
            ? CheckboxColumn::make()
            : null;
    }

    /**
     * @see SectionController::actionDeleteAll()
     */
    protected function getSelectionButton(): Stringable
    {
        $modal = Modal::make()
            ->title(Yii::t('cms', 'SECTION_DELETE_SELECTED'))
            ->text(Yii::t('skeleton', 'COMMON_CONFIRM_DELETE_SELECTED'))
            ->footer(Button::make()
                ->danger()
                ->text(Yii::t('cms', 'SECTION_DELETE_SELECTED'))
                ->icon('trash')
                ->post(['/admin/cms/section/delete-all'])
                ->attribute('hx-include', '[data-check]:checked'));

        return GridToolbarItem::make()
            ->content(Button::make()
                ->danger()
                ->text(Yii::t('cms', 'SECTION_DELETE_SELECTED'))
                ->icon('trash')
                ->modal($modal));
    }

    protected function getStatusColumn(): ?Column
    {
        $column = StatusIconColumn::make();

        // The icon is either the link to the section or the button that cycles its status, never both.
        return $this->enableStatusUpdate && $this->webuser->can(Entry::AUTH_ENTRY)
            ? $column->enableUpdate(true)
            : $column->url(fn (Section $model) => $model->getAdminRoute());
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeColumn::make()
                ->url(fn (Section $model) => $model->getAdminRoute());
    }

    protected function getContentColumn(): ?Column
    {
        return DataColumn::make()
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Section $section): Stringable|string
    {
        $html = $section->getGridContent();
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
            $html = (string)($section->getI18nAttribute('content') ?? '');
            $html = $section->getCustomAttribute('content') instanceof HtmlCustomAttribute
                ? strip_tags($html)
                : $html;

            $html = StringHelper::truncate($html, 100);
        }

        if (!$html) {
            $html = Yii::t('cms', 'COMMON_NO_TITLE');
            $cssClass = 'text-muted';
        }

        return A::make()
            ->content($html)
            ->href($section->getAdminRoute() ?: null)
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

    /**
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(Section $section): array
    {
        $buttons = [];

        if (
            $this->isSortable()
            && $this->provider->getCount() > 1
            && $this->webuser->can(Entry::AUTH_ENTRY)
        ) {
            $buttons[] = DraggableSortGridButton::make();
        }

        if ($this->webuser->can(Entry::AUTH_ENTRY)) {
            $buttons[] = ViewGridButton::make()
                ->model($section);
        }

        if ($this->showDeleteButton && $this->webuser->can(Entry::AUTH_ENTRY)) {
            $buttons[] = DeleteGridButton::make()
                ->model($section);
        }

        return $buttons;
    }
}
