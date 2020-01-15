<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\helpers\Url;

/**
 * Class CategoryGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class CategoryGridView extends GridView
{
    use ModuleTrait, CategoryGridTrait;

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
        $this->initAncestors();

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
                        'content' => $this->categoryDropdown(),
                        'options' => ['class' => 'col-12 col-md-3'],
                    ],
                    [
                        'content' => $this->getSearchInput(),
                        'options' => ['class' => 'col-12 col-md-6'],
                    ],
                    'options' => [
                        'class' => $this->dataProvider->category ? 'justify-content-between' : 'justify-content-end',
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
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Category $category) {
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
            'content' => function (Category $category) {
                return Html::a(Yii::$app->getFormatter()->asInteger($category->getBranchCount()), ['index', 'id' => $category->id], ['class' => 'badge']);
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
            'content' => function (Category $category) {
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
            'content' => function (Category $category) {
                $buttons = [];

                if ($this->isSortedByPosition()) {
                    $buttons[] = Html::tag('span', Icon::tag('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(Icon::tag('wrench'), ['update', 'id' => $category->id], ['class' => 'btn btn-secondary d-none d-md-inline-block']);
                return Html::buttons($buttons);
            }
        ];
    }

    /**
     * @param array $config
     * @return string|null
     */
    public function categoryDropdown($config = [])
    {
        if ($category = $this->dataProvider->category) {
            $config['label'] = Html::tag('strong', Html::encode($category->getI18nAttribute('name')));
            $config['paramName'] = 'id';

            $categories = Category::indentNestedTree($category->getAncestors() + [$category], Category::instance()->getI18nAttributeName('name'));

            foreach ($categories as $id => $name) {
                $config['items'][] = [
                    'label' => $name,
                    'url' => Url::current(['id' => $id, 'page' => null]),
                ];
            }

            return ButtonDropdown::widget($config);
        }

        return null;
    }

    /**
     * @return string
     */
    protected function renderCreateCategoryButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), ['create', 'id' => $this->dataProvider->category->id ?? null], ['class' => 'btn btn-primary']);
    }

    /**
     * @return bool
     */
    public function isSortedByPosition(): bool
    {
        return parent::isSortedByPosition() && !$this->dataProvider->searchString;
    }
}