<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FAS;
use yii\helpers\Url;

/**
 * Class EntryCategoryGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class EntryCategoryGridView extends GridView
{
    use ModuleTrait, CategoryTrait;

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
        'updated_at',
        'buttons',
    ];

    /**
     * @var array
     */
    private $_names;

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
                return Html::a($category->getTypeName(), ['category/update', 'id' => $category->id]);
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
                $html = Html::markKeywords(Html::encode($this->getIndentedCategoryName($category->id)), $this->search);
                $html = Html::tag('strong', Html::a($html, ['category/update', 'id' => $category->id]));

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
    public function updatedAtColumn()
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (CategoryForm $category) {
                return $category->entryCategory ? ($this->dateFormat ? $category->entryCategory->updated_at->format($this->dateFormat) : Timeago::tag($category->entryCategory->updated_at)) : null;
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
                return Html::buttons(Html::a(FAS::icon($category->entryCategory ? 'ban' : 'star'), [$category->entryCategory ? 'delete' : 'create', 'entry' => $this->dataProvider->entry->id, 'category' => $category->id], [
                    'class' => 'btn btn-secondary',
                ]));
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
     * @param $id
     * @return string
     */
    protected function getIndentedCategoryName($id)
    {
        if ($this->_names === null) {
            $this->_names = CategoryForm::indentNestedTree(static::getCategories(), $this->getModel()->getI18nAttributeName('name'), '–');
        }

        return $this->_names[$id] ?? '';
    }

    /**
     * @return CategoryForm
     */
    public function getModel()
    {
        return CategoryForm::instance();
    }
}