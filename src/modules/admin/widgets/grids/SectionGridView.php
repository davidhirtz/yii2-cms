<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\modules\admin\controllers\SectionController;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\StringHelper;

/**
 * Displays a grid of {@link Section} models for given {@link Entry}.
 * @property ActiveDataProvider $dataProvider
 */
class SectionGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;

    /**
     * @var Entry|null
     */
    public ?Entry $entry = null;

    /**
     * @var bool whether the delete-button should be visible in the section grid.
     */
    public bool $showDeleteButton = false;

    /**
     * @var array|string[] {@see SectionController::actionUpdateAll()}
     */
    public array $selectionRoute = ['/admin/section/update-all'];

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
                $this->assetCountColumn(),
                $this->buttonsColumn(),
            ];
        }

        $this->orderRoute = ['order', 'entry' => $this->entry->id];
        $this->selectionRoute = ['update-all', 'entry' => $this->entry->id];

        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid footer.
     */
    protected function initFooter(): void
    {
        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => $this->getCreateSectionButton() . ($this->showSelection ? $this->getSelectionButton() : ''),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    protected function getCreateSectionButton(): string
    {
        if (!Yii::$app->getUser()->can('sectionCreate', ['entry' => $this->entry])) {
            return '';
        }

        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Section')), ['/admin/section/create', 'entry' => $this->entry->id], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    protected function getSelectionButtonItems(): array
    {
        if (!Yii::$app->getUser()->can('sectionUpdate', ['entry' => $this->entry])) {
            return [];
        }

        return $this->statusSelectionButtonItems();
    }

    /**
     * @return array
     */
    public function typeColumn(): array
    {
        return [
            'attribute' => 'type',
            'visible' => count(Section::getTypes()) > 1,
            'content' => fn(Section $section) => Html::a($section->getTypeName(), ['update', 'id' => $section->id])
        ];
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function assetCountColumn(): array
    {
        return [
            'attribute' => 'asset_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableSectionAssets,
            'content' => function (Section $section) {
                if (!$section->hasAssetsEnabled()) {
                    return '';
                }

                $assetCount = Yii::$app->getFormatter()->asInteger($section->asset_count);
                return Html::a($assetCount, $this->getRoute($section, ['#' => 'assets']), ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => fn(Section $section): string => Html::buttons($this->getRowButtons($section))
        ];
    }

    /**
     * @return  array
     */
    protected function getRowButtons(Section $section): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortedByPosition() && $this->dataProvider->getCount() > 1 && $user->can('sectionOrder')) {
            $buttons[] = $this->getSortableButton();
        }

        if ($user->can('sectionUpdate', ['section' => $section])) {
            $buttons[] = $this->getUpdateButton($section);
        }

        if ($this->showDeleteButton && $user->can('sectionDelete', ['section' => $section])) {
            $buttons[] = $this->getDeleteButton($section);
        }

        return $buttons;
    }

    public function getModel(): Section
    {
        return Section::instance();
    }
}