<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\media\assets\AdminAsset;
use davidhirtz\yii2\media\modules\admin\widgets\forms\FileUpload;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

/**
 * Class AssetGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method AssetForm getModel()
 */
class AssetGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var EntryForm|SectionForm
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

            $this->setModel(new AssetForm);
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
                        'options' => ['class' => 'offset-md-4 col-md-8 col-lg-6'],
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
            'url' => $this->getRoute('/admin/cms/asset/create'),
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
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (AssetForm $asset) {
                return FAS::icon($asset->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $asset->getStatusName()
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function thumbnailColumn()
    {
        return [
            'headerOptions' => ['style' => 'width:150px'],
            'content' => function (AssetForm $asset) {
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
    public function typeColumn()
    {
        return [
            'attribute' => 'type',
            'visible' => count(AssetForm::getTypes()) > 1,
            'content' => function (AssetForm $asset) {
                return Html::a($asset->getTypeName(), ['cms/asset/update', 'id' => $asset->id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (AssetForm $asset) {

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
    public function buttonsColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (AssetForm $asset) {
                $buttons = [];

                if ($this->dataProvider->getCount() > 1) {
                    $buttons[] = Html::tag('span', FAS::icon('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(FAS::icon('image'), ['file/update', 'id' => $asset->file_id], ['class' => 'btn btn-secondary']);
                $buttons[] = Html::a(FAS::icon('wrench'), ['cms/asset/update', 'id' => $asset->id], ['class' => 'btn btn-secondary']);

                $buttons[] = Html::a(FAS::icon('trash'), ['cms/asset/delete', 'id' => $asset->id], [
                    'class' => 'btn btn-danger btn-delete-asset',
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
    protected function getRoute($action, $params = [])
    {
        return array_merge([$action, ($this->parent instanceof EntryForm ? 'entry' : 'section') => $this->parent->id], $params);
    }
}