<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionController;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\CounterColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
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

    public ?Entry $entry = null;

    /**
     * @var bool whether the delete-button should be visible in the section grid.
     */
    public bool $showDeleteButton = false;

    /**
     * @var array {@see SectionController::actionUpdateAll()}
     */
    public array $selectionRoute = ['/admin/section/update-all'];

    #[\Override]
    public function init(): void
    {
        if (!$this->dataProvider) {
            $this->dataProvider = new ArrayDataProvider([
                'allModels' => $this->entry->sections,
                'pagination' => false,
                'sort' => false,
            ]);
        }

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->entriesCountColumn(),
                $this->assetCountColumn(),
                $this->buttonsColumn(),
            ];
        }

        $this->orderRoute = ['order', 'entry' => $this->entry->id];
        $this->selectionRoute = ['update-all', 'entry' => $this->entry->id];

        $this->initFooter();

        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getCreateSectionButton() . ($this->showSelection ? $this->getSelectionButton() : ''),
                    'options' => ['class' => 'col'],
                ],
            ],
        ];
    }

    protected function getCreateSectionButton(): string
    {
        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_CREATE, ['entry' => $this->entry])) {
            return '';
        }

        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Section')), ['/admin/section/create', 'entry' => $this->entry->id], ['class' => 'btn btn-primary']);
    }

    #[\Override]
    protected function getSelectionButtonItems(): array
    {
        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['entry' => $this->entry])) {
            return [];
        }

        return $this->statusSelectionButtonItems();
    }

    public function typeColumn(): array
    {
        return [
            'attribute' => 'type',
            'visible' => count($this->getModel()::getTypes()) > 1,
            'content' => fn (Section $section) => Html::a($section->getTypeName(), ['update', 'id' => $section->id])
        ];
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Section $section) {
                $text = $section->getNameColumnContent() ?: (($name = $section->getI18nAttribute('name')) ? Html::tag('strong', $name) : null);
                $cssClass = null;

                if (!$text) {
                    if ($section->assets) {
                        $asset = current($section->assets);

                        if ($asset->file->hasPreview()) {
                            $text = Html::tag('div', Html::tag('div', '', [
                                'style' => 'background-image:url(' . ($asset->file->getTransformationUrl('admin') ?: $asset->file->getUrl()) . ');',
                                'class' => 'thumb',
                            ]), ['style' => 'width:120px']);
                        }
                    }
                }

                if (!$text) {
                    $text = $section->getI18nAttribute('content') ?? '';
                    $text = StringHelper::truncate($section->contentType == 'html' ? strip_tags($text) : $text, 100);
                }

                if (!$text) {
                    $text = Yii::t('cms', '[ No title ]');
                    $cssClass = 'text-muted';
                }

                return Html::a($text, ['update', 'id' => $section->id], ['class' => $cssClass]);
            }
        ];
    }

    public function assetCountColumn(): array
    {
        return [
            'attribute' => 'asset_count',
            'class' => CounterColumn::class,
            'route' => fn (Section $section) => $section->getAdminRoute() + ['#' => 'assets'],
            'visible' => static::getModule()->enableSectionAssets,
        ];
    }

    public function entriesCountColumn(): array
    {
        return [
            'attribute' => 'entry_count',
            'class' => CounterColumn::class,
            'route' => fn (Section $section) => $section->getAdminRoute() + ['#' => 'entries'],
            'visible' => $this->hasSectionEntries(),
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-end text-nowrap'],
            'content' => fn (Section $section): string => Html::buttons($this->getRowButtons($section))
        ];
    }

    protected function getRowButtons(Section $section): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortable()
            && $this->dataProvider->getCount() > 1
            && $user->can(Section::AUTH_SECTION_ORDER)) {
            $buttons[] = $this->getSortableButton();
        }

        if ($user->can(Section::AUTH_SECTION_UPDATE, ['section' => $section])) {
            $buttons[] = $this->getUpdateButton($section);
        }

        if ($this->showDeleteButton && $user->can(Section::AUTH_SECTION_DELETE, ['section' => $section])) {
            $buttons[] = $this->getDeleteButton($section);
        }

        return $buttons;
    }

    #[\Override]
    public function getModel(): Section
    {
        return Section::instance();
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
}
