<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\base\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;

/**
 * Class EntryCategoryGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryCategoryGridView
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class EntryCategoryGridView extends GridView
{
    use CategoryGridTrait;
    use CategoryTrait;
    use ModuleTrait;

    /**
     * @var string the category param name used in urls on {@link CategoryGridTrait}
     */
    public $categoryParamName = 'category';

    /**
     * @var bool whether frontend url should be displayed, defaults to false
     */
    public $showUrl = false;

    /**
     * @var array
     */
    private $_names;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->rowOptions) {
            $this->rowOptions = function (Category $category) {
                return [
                    'class' => $category->entryCategory ? 'is-selected' : null,
                ];
            };
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
        $this->initAncestors();
        parent::init();
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'label' => EntryCategory::instance()->getAttributeLabel('updated_at'),
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (Category $category) {
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
            'content' => function (Category $category) {
                // Make sure categories can always be removed even if they were not supposed to have entries enabled.
                return !$category->hasEntriesEnabled() && !$category->entryCategory ? '' : Html::buttons(Html::a(Icon::tag($category->entryCategory ? 'ban' : 'star'), [$category->entryCategory ? 'delete' : 'create', 'entry' => $this->dataProvider->entry->id, 'category' => $category->id], [
                    'class' => 'btn btn-primary',
                    'data-method' => 'post',
                ]));
            }
        ];
    }

    /**
     * @param Category $model
     * @param array $params
     * @return array|false
     */
    protected function getRoute($model, $params = [])
    {
        return ['category/update', 'id' => $model->id];
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function showCategoryAncestors($category): bool
    {
        return (bool)$this->dataProvider->searchString || $category->entryCategory;
    }
}