<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\helpers\FrontendLink;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\grids\columns\CounterColumn;
use davidhirtz\yii2\skeleton\widgets\grids\FilterDropdown;
use davidhirtz\yii2\skeleton\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use Yii;
use yii\helpers\Url;

/**
 * @property CategoryActiveDataProvider $dataProvider
 */
trait CategoryGridTrait
{
    use StatusGridViewTrait;
    use TypeGridViewTrait;
    use ModuleTrait;

    /**
     * @var string|null the format used to format the date values.
     */
    public ?string $dateFormat = null;

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->categoryDropdown(),
                $this->search->getToolbarItem(),
            ],
        ];
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Category $category) {
                $html = ($name = $category->getI18nAttribute('name'))
                    ? Html::markKeywords(Html::encode($name), $this->search->getKeywords())
                    : Yii::t('cms', '[ No title ]');

                $html = Html::a($html, $this->getRoute($category), [
                    'class' => $name ? 'strong' : 'strong text-muted',
                ]);

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

    public function branchCountColumn(): array
    {
        return [
            'attribute' => 'branchCount',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableNestedCategories,
            'content' => function (Category $category) {
                $route = [$this->categoryParamName => $category->id, 'page' => null, 'q' => null];
                return Html::a(Yii::$app->getFormatter()->asInteger($category->getBranchCount()), Url::current($route), ['class' => 'badge']);
            }
        ];
    }

    public function entryCountColumn(): array
    {
        return [
            'class' => CounterColumn::class,
            'attribute' => 'entry_count',
            'route' => fn (Category $category) => ['entry/index', 'category' => $category->id],
            'value' => fn (Category $category) => $category->hasEntriesEnabled() ? $category->entry_count : null,
        ];
    }

    public function getUrl(Category $category): string
    {
        $link = FrontendLink::tag($category);
        return $link ? Html::tag('div', $link, ['class' => 'd-none d-md-block small']) : '';
    }

    protected function getCategoryAncestors(Category $category): string
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

    public function showCategoryAncestors(Category $category): bool
    {
        return $this->dataProvider->searchString || $category->entryCategory;
    }

    protected function categoryDropdown(): ?FilterDropdown
    {
        return $this->dataProvider->category
            ? new FilterDropdown(
                $this->getCategoryDropdownItems($this->dataProvider->category),
                $this->dataProvider->category->getI18nAttribute('name'),
                'id',
            ) : null;
    }

    protected function getCategoryDropdownItems(Category $category): array
    {
        $attribute = $this->getModel()->getI18nAttributeName('name');
        return Category::indentNestedTree($category->getAncestors() + [$category], $attribute);
    }

    /**
     * Sets ancestors for all categories to avoid each record loading its ancestors from the database.
     * If no parent category is set simply set all loaded models and let {@see Category::setAncestors}
     * work it's magic.
     */
    protected function initAncestors(): void
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

    public function getModel(): Category
    {
        return Category::instance();
    }
}
