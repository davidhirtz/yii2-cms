<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\base\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;

/**
 * Class EntryCategoryGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryCategoryGridView
 *
 * @property CategoryActiveDataProvider $dataProvider
 */
class EntryCategoryGridView extends GridView
{
    use ModuleTrait, CategoryTrait, CategoryGridTrait;

    /**
     * @var array
     */
    public $columns = [
        'status',
        'type',
        'name',
        'entry_count',
        'updated_at',
        'buttons',
    ];

    /**
     * @var array
     */
    private $_names;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->rowOptions = function (Category $category) {
            return ['class' => $category->entryCategory ? 'is-selected' : null];
        };

        parent::init();
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Category $category) {
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
                return !$category->enableEntryCategory() ? '' : Html::buttons(Html::a(Icon::tag($category->entryCategory ? 'ban' : 'star'), [$category->entryCategory ? 'delete' : 'create', 'entry' => $this->dataProvider->entry->id, 'category' => $category->id], [
                    'class' => 'btn btn-secondary',
                    'data-method' => 'post',
                ]));
            }
        ];
    }

    /**
     * @param $id
     * @return string
     */
    protected function getIndentedCategoryName($id)
    {
        if ($this->_names === null) {
            $this->_names = Category::indentNestedTree(static::getCategories(), $this->getModel()->getI18nAttributeName('name'), '–');
        }

        return $this->_names[$id] ?? '';
    }
}