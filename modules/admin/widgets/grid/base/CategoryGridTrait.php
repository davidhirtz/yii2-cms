<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;


use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use Yii;
use yii\helpers\Url;

/**
 * Class CategoryGridTrait
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
trait CategoryGridTrait
{
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    /**
     * @var string
     */
    public $dateFormat;

    /**
     * Inits header.
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
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Category $category) {
                $html = Html::markKeywords(Html::encode($category->getI18nAttribute('name')), $this->search);
                $html = Html::tag('strong', Html::a($html, $this->getRoute($category)));

                if ($this->showCategoryAncestors($category)) {
                    $html .= Html::tag('div', $this->getCategoryAncestors($category), ['class' => 'small']);
                }

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
            'visible' => static::getModule()->enableNestedCategories,
            'content' => function (Category $category) {
                return Html::a(Yii::$app->getFormatter()->asInteger($category->getBranchCount()), Url::current([$this->categoryParamName => $category->id, 'page' => null]), ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function entryCountColumn()
    {
        return [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\CounterColumn',
            'attribute' => 'entry_count',
            'route' => function (Category $category) {
                return ['entry/index', 'category' => $category->id];
            },
            'value' => function (Category $category) {
                return $category->hasEntriesEnabled() ? $category->entry_count : null;
            },
        ];
    }

    /**
     * @param Category $category
     * @return string
     */
    public function getUrl($category)
    {
        if ($route = $category->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();
            $url = $category->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);

            if ($url) {
                return Html::tag('div', Html::a($url, $url, ['target' => '_blank']), ['class' => 'd-none d-md-block small']);
            }
        }

        return '';
    }

    /**
     * @param Category $category
     * @return string
     */
    protected function getCategoryAncestors($category)
    {
        if ($category->parent_id) {
            $parents = [];

            foreach ($category->getAncestors() as $parent) {
                $parents[] = Html::a(Html::encode($parent->name), $this->getRoute($parent));
            }

            return implode(' / ', $parents);
        }

        return '';
    }

    /**
     * @param array $config
     * @return string|null
     */
    protected function categoryDropdown($config = [])
    {
        if ($category = $this->dataProvider->category) {
            $config['label'] = Html::tag('strong', Html::encode($category->getI18nAttribute('name')));
            $config['paramName'] = 'id';

            $categories = Category::indentNestedTree($category->getAncestors() + [$category], Category::instance()->getI18nAttributeName('name'));

            foreach ($categories as $id => $name) {
                $config['items'][] = [
                    'label' => $name,
                    'url' => Url::current([$this->categoryParamName => $id, 'page' => null]),
                ];
            }

            return ButtonDropdown::widget($config);
        }

        return null;
    }

    /**
     * Sets ancestors for all categories to avoid each record loading it's ancestors from the database.
     * If no parent category is set simply set all loaded models and let {@link Category::setAncestors}
     * work it's magic.
     */
    protected function initAncestors()
    {
        if ($this->dataProvider->category) {
            $categories = [$this->dataProvider->category->id => $this->dataProvider->category] + $this->dataProvider->category->ancestors;
        } else {
            $categories = !$this->dataProvider->searchString ? $this->dataProvider->getModels() : Category::find()
                ->indexBy('id')
                ->all();
        }

        foreach ($this->dataProvider->getModels() as $category) {
            $category->setAncestors($categories);
        }
    }

    /**
     * @return Category
     */
    public function getModel()
    {
        return Category::instance();
    }
}