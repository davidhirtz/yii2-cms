<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\assets\AdminAsset;
use davidhirtz\yii2\media\models\AssetParentInterface;
use davidhirtz\yii2\media\modules\admin\widgets\UploadTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

/**
 * Class AssetGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetGridView
 *
 * @property ActiveDataProvider $dataProvider
 */
class AssetGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;
    use UploadTrait;

    /**
     * @var AssetParentInterface
     */
    public $parent;

    /**
     * @var string
     */
    public $layout = '{items}{footer}';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->dataProvider) {
            $this->dataProvider = new ArrayDataProvider([
                'allModels' => $this->parent->assets,
                'pagination' => false,
                'sort' => false,
            ]);

            $this->setModel(Asset::instance());
        }

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

        $this->orderRoute = $this->getParentRoute('cms/asset/order');
        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid footer.
     */
    protected function initFooter()
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

    /**
     * @return array
     */
    protected function getFooterButtons()
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

    /**
     * @return string
     */
    protected function getAssetsButton()
    {
        return Html::a(Html::iconText('images', Yii::t('cms', 'Library')), $this->getParentRoute('cms/asset/index'), [
            'class' => 'btn btn-primary',
        ]);
    }

    /**
     * @return string
     */
    public function renderItems(): string
    {
        return Html::tag('div', parent::renderItems(), ['id' => 'files']);
    }

    /**
     * @return array
     */
    public function thumbnailColumn(): array
    {
        return [
            'headerOptions' => ['style' => 'width:150px'],
            'content' => function (Asset $asset) {
                return !$asset->file->hasPreview() ? '' : Html::tag('div', '', [
                    'style' => 'background-image:url(' . ($asset->file->getTransformationUrl('admin') ?: $asset->file->getUrl()) . ');',
                    'class' => 'thumb',
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Asset $asset) {
                if ($name = $asset->getI18nAttribute('name')) {
                    return Html::tag('strong', Html::a($name, $this->getRoute($asset)));
                }

                return Html::a($asset->file->name, $this->getRoute($asset), ['class' => 'text-muted']);
            }
        ];
    }

    /**
     * @return array
     */
    public function dimensionsColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('dimensions'),
            'content' => function (Asset $asset) {
                return $asset->file->hasDimensions() ? $asset->file->getDimensions() : '-';
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
            'content' => function (Asset $asset) {
                return Html::buttons($this->getRowButtons($asset));
            }
        ];
    }

    /**
     * @param Asset $asset
     * @return array
     */
    protected function getRowButtons(Asset $asset)
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

    /**
     * @param Asset $asset
     * @return string
     */
    protected function getFileUpdateButton($asset)
    {
        return Html::a(Icon::tag('image'), ['file/update', 'id' => $asset->file_id], [
            'class' => 'btn btn-secondary d-none d-md-inline-block',
            'title' => Yii::t('media', 'Edit File'),
            'data-toggle' => 'tooltip',
            'target' => '_blank',
        ]);
    }

    /**
     * @param Asset $model
     * @return string
     */
    protected function getDeleteButton($model)
    {
        $options = [
            'class' => 'btn btn-danger btn-delete-asset d-none d-md-inline-block',
            'data-confirm' => Yii::t('yii', 'Are you sure you want to remove this asset?'),
            'data-target' => '#' . $this->getRowId($model),
            'data-ajax' => 'remove',
        ];

        if (Yii::$app->getUser()->can('fileDelete', ['file' => $model->file])) {
            $options['data-delete-message'] = Yii::t('cms', 'Permanently delete related files');
            $options['data-delete-url'] = Url::to(['file/delete', 'id' => $model->file_id]);
        }

        return Html::a(Icon::tag('trash'), ['cms/asset/delete', 'id' => $model->id], $options);
    }

    /**
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function getParentRoute(string $action, $params = []): array
    {
        return array_merge([$action, ($this->parent instanceof Entry ? 'entry' : 'section') => $this->parent->id], $params);
    }

    /**
     * @param Asset $model
     * @param array $params
     * @return array|false
     */
    protected function getRoute($model, $params = [])
    {
        if (!Yii::$app->getUser()->can($model->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $model])) {
            return false;
        }

        return array_merge(['cms/asset/update', 'id' => $model->id], $params);
    }

    /**
     * @return array
     */
    protected function getCreateRoute()
    {
        return $this->getParentRoute('/admin/cms/asset/create', [
            'folder' => Yii::$app->getRequest()->get('folder'),
        ]);
    }

    /**
     * @return Asset
     */
    public function getModel()
    {
        return Asset::instance();
    }
}