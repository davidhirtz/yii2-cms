<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetThumbnailColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\assets\AdminAsset;
use davidhirtz\yii2\media\models\interfaces\AssetInterface;
use davidhirtz\yii2\media\models\interfaces\AssetParentInterface;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\UploadTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;
use yii\db\ExpressionInterface;
use yii\helpers\Url;

/**
 * @property ActiveDataProvider $dataProvider
 */
class AssetGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;
    use UploadTrait;

    public ?AssetParentInterface $parent = null;

    /**
     * @var string
     */
    public $layout = '{header}{items}{footer}';

    /**
     * @var int|ExpressionInterface|null the maximum number of assets loaded for `$parent`
     */
    public int|ExpressionInterface|null $maxAssetCount = 100;

    public function init(): void
    {
        $this->dataProvider ??= new ActiveDataProvider([
            'query' => $this->getParentAssetQuery(),
            'pagination' => false,
            'sort' => false,
        ]);

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->thumbnailColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->dimensionsColumn(),
                $this->buttonsColumn(),
            ];
        }

        if (Yii::$app->getUser()->can('fileCreate')) {
            AdminAsset::register($view = $this->getView());
            $view->registerJs('Skeleton.deleteFilesWithAssets();');
            $view->registerJs('Skeleton.mediaFileImport();');
        }

        /**
         * @see EntryController::actionOrder()
         * @see SectionController::actionOrder()
         */
        $this->orderRoute = $this->getParentRoute('cms/asset/order');

        $this->initFooter();

        parent::init();
    }

    protected function initFooter(): void
    {
        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => Html::buttons($this->getFooterButtons()),
                        'options' => ['class' => 'offset-md-3 col-md-9'],
                    ],
                ],
            ];
        }
    }

    protected function getFooterButtons(): array
    {
        $isEntry = $this->parent instanceof Entry;
        $user = Yii::$app->getUser();
        $buttons = [];

        if (($isEntry && $user->can('entryAssetCreate', ['entry' => $this->parent])) ||
            $user->can('sectionAssetCreate', ['section' => $this->parent])) {
            if ($user->can('fileCreate')) {
                $buttons[] = $this->getUploadFileButton();
                $buttons[] = $this->getImportFileButton();
            }

            $buttons[] = $this->getAssetsButton();
        }

        return $buttons;
    }

    protected function getAssetsButton(): string
    {
        return Html::a(Html::iconText('images', Yii::t('cms', 'Link assets')), $this->getIndexRoute(), [
            'class' => 'btn btn-primary',
        ]);
    }

    public function renderItems(): string
    {
        return Html::tag('div', parent::renderItems(), ['id' => 'files']);
    }

    public function thumbnailColumn(): array
    {
        return ['class' => AssetThumbnailColumn::class];
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function ($asset) {
                /** @var Asset $asset */
                if ($name = $asset->getI18nAttribute('name')) {
                    return Html::tag('strong', Html::a(Html::encode($name), $this->getRoute($asset)));
                }

                return Html::a(Html::encode($asset->file->name), $this->getRoute($asset), ['class' => 'text-muted']);
            }
        ];
    }

    public function dimensionsColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('dimensions'),
            'content' => fn (AssetInterface $asset) => $asset->file->hasDimensions() ? $asset->file->getDimensions() : '-'
        ];
    }

    protected function getParentAssetQuery(): ActiveQuery
    {
        return $this->parent->getAssets()
            ->andWhere(['section_id' => $this->parent instanceof Section ? $this->parent->id : null])
            ->with(['file', 'file.folder'])
            ->limit($this->maxAssetCount);
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => fn ($asset): string => Html::buttons($this->getRowButtons($asset))
        ];
    }

    /**
     * @param Asset $asset
     */
    protected function getRowButtons(AssetInterface $asset): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortedByPosition() && $this->dataProvider->getCount() > 1) {
            if (($asset->isEntryAsset() && $user->can('entryAssetOrder', ['entry' => $asset->entry])) ||
                $user->can('sectionAssetOrder', ['section' => $asset->section])) {
                $buttons[] = $this->getSortableButton();
            }
        }

        if ($user->can('fileUpdate', ['file' => $asset->file])) {
            $buttons[] = $this->getFileUpdateButton($asset);
        }

        if ($user->can($asset->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $asset])) {
            $buttons[] = $this->getUpdateButton($asset);
        }

        if ($user->can($asset->isEntryAsset() ? 'entryAssetDelete' : 'sectionAssetDelete', ['asset' => $asset])) {
            $buttons[] = $this->getDeleteButton($asset);
        }

        return $buttons;
    }

    protected function getFileUpdateButton(AssetInterface $asset): string
    {
        return Html::a(Icon::tag('image'), ['file/update', 'id' => $asset->file_id], [
            'class' => 'btn btn-secondary d-none d-md-inline-block',
            'title' => Yii::t('media', 'Edit File'),
            'data-toggle' => 'tooltip',
            'target' => '_blank',
        ]);
    }

    /**
     * @param AssetInterface $model
     */
    protected function getDeleteButton(ActiveRecordInterface $model): string
    {
        $options = [
            'class' => 'btn btn-danger btn-delete-asset d-none d-md-inline-block',
            'data-confirm' => Yii::t('cms', 'Are you sure you want to remove this asset?'),
            'data-target' => '#' . $this->getRowId($model),
            'data-ajax' => 'remove',
        ];

        if (Yii::$app->getUser()->can('fileDelete', ['file' => $model->file])) {
            $options['data-delete-message'] = Yii::t('cms', 'Permanently delete related files');
            $options['data-delete-url'] = Url::to(['file/delete', 'id' => $model->file_id]);
        }

        return Html::a(Icon::tag('trash'), $this->getDeleteRoute($model), $options);
    }

    protected function getParentRoute(string $action, $params = []): array
    {
        return array_merge([$action, ($this->parent instanceof Entry ? 'entry' : 'section') => $this->parent->id], $params);
    }

    /**
     * @param Asset $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): false|array
    {
        if (!Yii::$app->getUser()->can($model->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $model])) {
            return false;
        }

        return ['cms/asset/update', 'id' => $model->id, ...$params];
    }

    protected function getCreateRoute(): array
    {
        return $this->getParentRoute('/admin/cms/asset/create', [
            'folder' => Yii::$app->getRequest()->get('folder'),
        ]);
    }

    protected function getDeleteRoute(ActiveRecordInterface $model, array $params = []): array
    {
        return ['/admin/asset/delete', 'id' => $model->getPrimaryKey(), ...$params];
    }

    protected function getIndexRoute(): array
    {
        return $this->getParentRoute('cms/asset/index');
    }

    public function getModel(): Asset
    {
        return Asset::instance();
    }
}
