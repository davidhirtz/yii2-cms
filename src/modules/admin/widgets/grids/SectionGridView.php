<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Widgets\Grids\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Grids\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Grids\Buttons\DraggableSortButton;
use Hirtz\Skeleton\Widgets\Grids\Buttons\ViewButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonsColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\CounterColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\StatusGridViewTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\StringHelper;

/**
 * @extends GridView<Section>
 * @property ActiveDataProvider|ArrayDataProvider|null $dataProvider
 */
class SectionGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    public Entry $entry;
    public bool $showDeleteButton = false;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'section-grid');

        $this->dataProvider ??= new ArrayDataProvider([
            'allModels' => $this->entry->sections,
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            $this->statusColumn(),
            $this->typeColumn(),
            $this->nameColumn(),
            $this->entriesCountColumn(),
            $this->assetCountColumn(),
            $this->buttonsColumn(),
        ];

        $this->orderRoute = ['order', 'entry' => $this->entry->id];

        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateButton(),
            ],
        ];
    }

    protected function getCreateButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_CREATE, ['entry' => $this->entry])) {
            return null;
        }

        return new CreateButton(Yii::t('skeleton', 'New Redirect'), ['/admin/section/create', 'entry' => $this->entry->id]);
    }

    protected function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Section $section) {
                $html = $section->getNameColumnContent();

                if (!$html) {
                    $name = $section->getI18nAttribute('name');
                    $html = $name ? Html::tag('strong', $name) : null;
                }

                $cssClass = null;

                if (!$html && $section->assets) {
                    $asset = current($section->assets);

                    if ($asset->file->hasPreview()) {
                        $html = Html::tag('div', Html::tag('div', '', [
                            'style' => 'background-image:url(' . ($asset->file->getTransformationUrl('admin') ?: $asset->file->getUrl()) . ');',
                            'class' => 'thumb',
                        ]), ['style' => 'width:120px']);
                    }
                }

                if (!$html) {
                    $html = $section->getI18nAttribute('content') ?? '';
                    $html = StringHelper::truncate($section->contentType === 'html' ? strip_tags($html) : $html, 100);
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
        ];
    }

    protected function assetCountColumn(): array
    {
        return [
            'attribute' => 'asset_count',
            'class' => CounterColumn::class,
            'route' => fn (Section $section) => $section->getAdminRoute() + ['#' => 'assets'],
            'visible' => static::getModule()->enableSectionAssets,
        ];
    }

    protected function entriesCountColumn(): array
    {
        return [
            'attribute' => 'entry_count',
            'class' => CounterColumn::class,
            'route' => fn (Section $section) => $section->getAdminRoute() + ['#' => 'entries'],
            'visible' => $this->hasSectionEntries(),
        ];
    }

    protected function hasSectionEntries(): bool
    {
        if (!static::getModule()->enableSectionEntries) {
            return false;
        }

        foreach ($this->entry->sections as $section) {
            if ($section->entry_count) {
                return true;
            }
        }

        return false;
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => $this->getRowButtons(...)
        ];
    }

    protected function getRowButtons(Section $section): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if (
            $this->isSortable()
            && $this->dataProvider->getCount() > 1
            && $user->can(Section::AUTH_SECTION_ORDER)
        ) {
            $buttons[] = Yii::createObject(DraggableSortButton::class);
        }

        if ($user->can(Section::AUTH_SECTION_UPDATE, ['section' => $section])) {
            $buttons[] = Yii::createObject(ViewButton::class, [$section]);
        }

        if ($this->showDeleteButton && $user->can(Section::AUTH_SECTION_DELETE, ['section' => $section])) {
            $buttons[] = Yii::createObject(DeleteButton::class, [$section]);
        }

        return $buttons;
    }

    #[Override]
    public function getModel(): Section
    {
        return Section::instance();
    }
}
