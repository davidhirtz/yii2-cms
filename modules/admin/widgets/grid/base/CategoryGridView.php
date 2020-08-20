<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Class CategoryGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\CategoryGridView
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class CategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    /**
     * @var string the category param name used in urls on {@link CategoryGridTrait}.
     */
    public $categoryParamName = 'id';

    /**
     * @var bool whether frontend url should be displayed, defaults to true
     */
    public $showUrl = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->dataProvider->category) {
            $this->orderRoute = ['order', 'id' => $this->dataProvider->category->id];
        }

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->branchCountColumn(),
                $this->entryCountColumn(),
                $this->updatedAtColumn(),
                $this->buttonsColumn(),
            ];
        }

        $this->initHeader();
        $this->initFooter();
        $this->initAncestors();

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

    /**
     * @return bool
     */
    public function showCategoryAncestors(): bool
    {
        return (bool)$this->dataProvider->searchString;
    }
}