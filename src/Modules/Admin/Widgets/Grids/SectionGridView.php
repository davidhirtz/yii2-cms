<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\EntryRelationCountColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Widgets\Buttons\DraggableSortButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\SelectionTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\MissingTranslationsColumn;
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
    use SelectionTrait;

    public bool $showDeleteButton = false;
    public int $maxThumbnailCount = 3;

    protected string $layout = '{summary}{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'section-grid-view';
        $this->orderRoute = ['order', 'entry' => $this->provider->entry->id];

        $this->configureSelection();

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getContentColumn(),
            $this->getMissingTranslationsColumn(),
            $this->getEntriesCountColumn(),
            $this->getAssetCountColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'SECTION_GRID_SUMMARY_EMPTY'))
            ->emptyLink(
                Yii::t('cms', 'SECTION_GRID_SUMMARY_EMPTY_LINK'),
                $this->webuser->can(Entry::AUTH_ENTRY)
                    ? ['/admin/cms/section/create', 'entry' => $this->provider->entry->id]
                    : null,
            )
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    /**
     * A single section is deleted through its own row rather than through a selection of one.
     */
    protected function canDeleteSelection(): bool
    {
        return $this->provider->getCount() > 1 && $this->webuser->can(Entry::AUTH_ENTRY);
    }

    protected function canUpdateSelection(): bool
    {
        return $this->canDeleteSelection();
    }

    /**
     * @see SectionController::actionUpdateAll()
     * @return array<int|string, mixed>|null
     */
    protected function getUpdateSelectionRoute(): ?array
    {
        return ['/admin/cms/section/update-all'];
    }

    protected function getDeleteSelectionLabel(): string
    {
        return Yii::t('cms', 'SECTION_BUTTON_DELETE_SELECTED');
    }

    protected function getDeleteSelectionMessage(): string
    {
        return Yii::t('cms', 'SECTION_CONFIRM_DELETE_SELECTED');
    }

    /**
     * @see SectionController::actionDeleteAll()
     * @return array<int|string, mixed>
     */
    protected function getDeleteSelectionRoute(): array
    {
        return ['/admin/cms/section/delete-all'];
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
            $name = $section->getVisibleAttribute('name');
            $html = $name ? Div::make()->class('strong')->text($name) : null;
        }

        if (!$html) {
            $html = $this->getThumbnails($section);
        }

        if (!$html) {
            $text = (string)($section->getVisibleAttribute('content') ?? '');
            $text = $section->getCustomAttribute('content') instanceof HtmlCustomAttribute
                ? html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5)
                : $text;

            $html = Html::encode(StringHelper::truncate($text, 100));
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

    protected function getThumbnails(Section $section): ?Stringable
    {
        $thumbnails = [];

        foreach ($section->getVisibleAssets() as $asset) {
            // The container's thumbnail decides what has a preview (media-video draws one for a video).
            $thumbnail = Thumbnail::make()->file($asset->file)->render();

            if ($thumbnail !== '') {
                $thumbnails[] = $thumbnail;

                if (count($thumbnails) >= $this->maxThumbnailCount) {
                    break;
                }
            }
        }

        return $thumbnails ? Div::make()->class('img-thumbnails')->content(...$thumbnails) : null;
    }

    protected function getAssetCountColumn(): ?Column
    {
        return AssetCountColumn::make();
    }

    protected function getEntriesCountColumn(): ?Column
    {
        return EntryRelationCountColumn::make();
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
            $buttons[] = DraggableSortButton::make();
        }

        if ($this->webuser->can(Entry::AUTH_ENTRY)) {
            $buttons[] = ViewGridButton::make()
                ->model($section);
        }

        if ($this->showDeleteButton && $this->webuser->can(Entry::AUTH_ENTRY)) {
            $buttons[] = DeleteGridButton::make()
                ->label(Yii::t('cms', 'SECTION_BUTTON_DELETE'))
                ->title(Yii::t('cms', 'SECTION_CONFIRM_DELETE'))
                ->model($section);
        }

        return $buttons;
    }

    /**
     * Shown only while a record on the page lacks a translation.
     */
    protected function getMissingTranslationsColumn(): ?Column
    {
        return MissingTranslationsColumn::make();
    }
}
