<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\assets\AdminAsset;
use davidhirtz\yii2\media\modules\admin\widgets\forms\FileUpload;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

/**
 * Class AssetGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method Asset getModel()
 */
class AssetGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var Entry|Section
     */
    public $parent;

    /**
     * @var array
     */
    public $columns = [
        'status',
        'thumbnail',
        'type',
        'name',
        'dimensions',
        'buttons',
    ];

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

            $this->setModel(new Asset);
        }

        if (Yii::$app->getUser()->can('upload')) {
            AdminAsset::register($view = $this->getView());
            $view->registerJs('deleteFilesWithAssets()');
        }

        $this->orderRoute = $this->getRoute('cms/asset/order');
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
                        'content' => $this->getButtons(),
                        'visible' => Yii::$app->getUser()->can('upload'),
                        'options' => ['class' => 'offset-md-3 col-md-8'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    protected function getButtons()
    {
        return Html::buttons([
            Html::tag('div', Html::iconText('plus', Yii::t('cms', 'Upload') . $this->getFileUploadWidget()), ['class' => 'btn btn-primary btn-upload']),
            Html::a(Html::iconText('images', Yii::t('cms', 'Library')), $this->getRoute('cms/asset/index'), ['class' => 'btn btn-primary']),
        ]);
    }

    /**
     * @return string
     */
    protected function getFileUploadWidget()
    {
        return FileUpload::widget([
            'url' => $this->getRoute('/admin/cms/asset/create', [
                'folder' => Yii::$app->getRequest()->get('folder'),
            ]),
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
    public function statusColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (Asset $asset) {
                return Icon::tag($asset->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $asset->getStatusName()
                ]);
            }
        ];
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
    public function typeColumn(): array
    {
        return [
            'attribute' => 'type',
            'visible' => count(Asset::getTypes()) > 1,
            'content' => function (Asset $asset) {
                return Html::a($asset->getTypeName(), ['cms/asset/update', 'id' => $asset->id]);
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
                    return Html::tag('strong', Html::a($name, ['cms/asset/update', 'id' => $asset->id]));
                }

                return Html::a($asset->file->name, ['cms/asset/update', 'id' => $asset->id], ['class' => 'text-muted']);
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
                $buttons = [];

                if ($this->dataProvider->getCount() > 1) {
                    $buttons[] = Html::tag('span', Icon::tag('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(Icon::tag('image'), ['file/update', 'id' => $asset->file_id], [
                    'class' => 'btn btn-secondary d-none d-md-inline-block',
                    'data-toggle' => 'tooltip',
                    'title' => Yii::t('media', 'Edit File'),
                ]);

                $buttons[] = Html::a(Icon::tag('wrench'), ['cms/asset/update', 'id' => $asset->id], [
                    'class' => 'btn btn-secondary',
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

                return Html::buttons($buttons);
            }
        ];
    }

    /**
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function getRoute($action, $params = []): array
    {
        return array_merge([$action, ($this->parent instanceof Entry ? 'entry' : 'section') => $this->parent->id], $params);
    }
}