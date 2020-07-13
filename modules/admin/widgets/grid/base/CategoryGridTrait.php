<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;


use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use Yii;

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
     * Sets ancestors for all categories to avoid each record loading it's ancestors from the database.
     * If no parent category is set simply set all loaded models and let {@link Category::setAncestors}
     * work it's magic.
     */
    protected function initAncestors()
    {
        if ($this->dataProvider->category) {
            $categories = $this->dataProvider->category->ancestors;
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