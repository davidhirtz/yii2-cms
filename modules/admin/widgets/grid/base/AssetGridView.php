<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\assets\AdminAsset;
use davidhirtz\yii2\media\models\AssetParentInterface;
use davidhirtz\yii2\media\modules\admin\widgets\UploadTrait;
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
     * @inheritdoc
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

        if (Yii::$app->getUser()->can('upload')) {
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
                        'visible' => Yii::$app->getUser()->can('upload'),
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
        return [$this->getUploadFileButton(), $this->getImportFileButton(), $this->getAssetsButton()];
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
     * @return string|null
     */
    public function renderItems()
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
    protected function getRowButtons($asset)
    {
        $buttons = [];

        if ($this->isSortedByPosition() && $this->dataProvider->getCount() > 1) {
            $buttons[] = Html::tag('span', Icon::tag('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
        }

        $buttons[] = Html::a(Icon::tag('image'), ['file/update', 'id' => $asset->file_id], [
            'class' => 'btn btn-secondary d-none d-md-inline-block',
            'title' => Yii::t('media', 'Edit File'),
            'data-toggle' => 'tooltip',
            'target' => '_blank',
        ]);

        $buttons[] = Html::a(Icon::tag('wrench'), $this->getRoute($asset), [
            'class' => 'btn btn-primary',
            'data-toggle' => 'tooltip',
            'title' => Yii::t('cms', 'Edit Asset'),
        ]);

        $buttons[] = Html::a(Icon::tag('trash'), ['cms/asset/delete', 'id' => $asset->id], [
            'class' => 'btn btn-danger btn-delete-asset d-none d-md-inline-block',
            'data-confirm' => Yii::t('yii', 'Are you sure you want to remove this asset?'),
            'data-ajax' => 1,
            'data-delete-message' => Yii::t('cms', 'Permanently delete related files'),
            'data-delete-url' => Url::to(['file/delete', 'id' => $asset->file_id]),
        ]);

        return $buttons;
    }

    /**
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function getParentRoute($action, $params = []): array
    {
        return array_merge([$action, ($this->parent instanceof Entry ? 'entry' : 'section') => $this->parent->id], $params);
    }

    /**
     * @param Asset $model
     * @param array $params
     * @return array
     */
    protected function getRoute($model, $params = []): array
    {
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