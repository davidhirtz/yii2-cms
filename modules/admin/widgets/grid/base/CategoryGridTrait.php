<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;


use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Class CategoryGridTrait.
 */
trait CategoryGridTrait
{
    /**
     * @var bool
     */
    public $showUrl = true;

    /**
     * @var string
     */
    public $dateFormat;

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (Category $category) {
                return Icon::tag($category->getStatusIcon(), [
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
            'visible' => count(Category::getTypes()) > 1,
            'content' => function (Category $category) {
                return Html::a($category->getTypeName(), ['update', 'id' => $category->id]);
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
            'content' => function (Category $category) {
                return Html::a(Yii::$app->getFormatter()->asInteger($category->entry_count), ['entry/index', 'category' => $category->id], ['class' => 'badge']);
            }
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
     * @return Category
     */
    public function getModel()
    {
        return Category::instance();
    }
}