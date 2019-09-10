<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\helpers\Url;

/**
 * Class CategoryGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class CategoryGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var bool
     */
    public $showUrl = true;

    /**
     * @var string
     */
    public $dateFormat;

    /**
     * @var array
     */
    public $columns = [
        'status',
        'type',
        'name',
        'branchCount',
        'entry_count',
        'updated_at',
        'buttons',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->dataProvider->category) {
            $this->orderRoute = ['order', 'id' => $this->dataProvider->category->id];
        }

        $this->initHeader();
        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid header.
     */
    protected function initHeader()
    {
        if ($this->header === null) {
            $this->header = [
                [
                    [
                        'content' => $this->getSearchInput(),
                        'options' => ['class' => 'col-12 col-md-6'],
                    ],
                    'options' => [
                        'class' => 'justify-content-end',
                    ],
                ],
            ];
        }
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
                        'content' => $this->renderCreateCategoryButton(),
                        'visible' => Yii::$app->getUser()->can('author'),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    public function renderSummary()
    {
        if($this->dataProvider->category) {
            // @todo category path
        }

        return parent::renderSummary();
    }

    /**
     * @return string
     */
    protected function renderCreateCategoryButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), ['create', 'id' => $this->dataProvider->category->id ?? null], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (CategoryForm $category) {
                return FAS::icon($category->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $category->getStatusName()
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
            'visible' => count(CategoryForm::getTypes()) > 1,
            'content' => function (CategoryForm $category) {
                return Html::a($category->getTypeName(), ['update', 'id' => $category->id]);
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
            'content' => function (CategoryForm $category) {
                $html = Html::markKeywords(Html::encode($category->getI18nAttribute('name')), $this->search);
                $html = Html::tag('strong', Html::a($html, ['update', 'id' => $category->id]));

                if ($this->showUrl) {
                    $html .= $this->getUrl($category);
                }


                return $html;
            }
        ];
    }

    /**
     * @return array
     */
    public function branchCountColumn()
    {
        return [
            'attribute' => 'branchCount',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => function (CategoryForm $category) {
                return Html::a(Yii::$app->getFormatter()->asInteger($category->getBranchCount()), ['index', 'id' => $category->id], ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function entryCountColumn()
    {
        return [
            'attribute' => 'entry_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableSections,
            'content' => function (CategoryForm $category) {
                return Html::a(Yii::$app->getFormatter()->asInteger($category->entry_count), ['entry/index', 'category' => $category->id], ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (CategoryForm $category) {
                return $this->dateFormat ? $category->updated_at->format($this->dateFormat) : Timeago::tag($category->updated_at);
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
            'content' => function (CategoryForm $category) {
                $buttons = [];

                if ($this->getIsSortedByPosition()) {
                    $buttons[] = Html::tag('span', FAS::icon('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(FAS::icon('wrench'), ['update', 'id' => $category->id], ['class' => 'btn btn-secondary d-none d-md-inline-block']);
                return Html::buttons($buttons);
            }
        ];
    }

    /**
     * @param CategoryForm $category
     * @return string
     */
    public function getUrl($category)
    {
        $url = Url::to($category->getRoute(), true);
        return Html::tag('div', Html::a($url, $url, ['target' => '_blank']), ['class' => 'd-none d-md-block small']);
    }

    /**
     * @return CategoryForm
     */
    public function getModel()
    {
        return CategoryForm::instance();
    }
}