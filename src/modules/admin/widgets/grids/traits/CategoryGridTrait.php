<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids\traits;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\collections\CategoryCollection;
use Hirtz\Cms\modules\admin\data\CategoryActiveDataProvider;
use Hirtz\Cms\modules\admin\helpers\FrontendLink;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\widgets\grids\columns\BadgeColumn;
use Hirtz\Skeleton\widgets\grids\columns\Column;
use Hirtz\Skeleton\widgets\grids\columns\DataColumn;
use Hirtz\Skeleton\widgets\grids\toolbars\FilterDropdown;
use Hirtz\Skeleton\widgets\grids\traits\StatusGridViewTrait;
use Hirtz\Skeleton\widgets\grids\traits\TypeGridViewTrait;
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

    protected function initHeader(): void
    {
        $this->header ??= [
            $this->getCategoryDropdown(),
            $this->search->getToolbarItem(),
        ];
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property('name')
            ->property($this->model->getI18nAttributeName('name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Category $category): string
    {
        $name = $category->getI18nAttribute('name');

        $html = $name
            ? Html::markKeywords(Html::encode($name), $this->search->getKeywords())
            : Yii::t('cms', '[ No title ]');

        $html = A::make()
            ->class($name ? 'strong' : 'strong text-muted')
            ->content($html)
            ->href($this->getRoute($category));

        if ($this->showCategoryAncestors($category)) {
            $html .= Div::make()
                ->class('small')
                ->content($this->getCategoryAncestors($category));
        }

        if ($this->showUrl) {
            $html .= $this->getUrl($category);
        }

        return $html;
    }

    protected function getBranchCountColumn(): ?Column
    {
        if (!($this->provider->category?->hasDescendantsEnabled()
            ?? static::getModule()->enableNestedCategories)) {
            return null;
        }

        return BadgeColumn::make()
            ->property('branchCount')
            ->url(fn (Category $category) => Url::current([
                $this->categoryParamName => $category->id,
                'page' => null,
                'q' => null,
            ]));
    }

    protected function getEntryCountColumn(): ?Column
    {
        return BadgeColumn::make()
            ->property('entry_count')
            ->url(fn (Category $category) => ['entry/index', 'category' => $category->id])
            ->value(fn (Category $category) => $category->hasEntriesEnabled() ? $category->entry_count : null);
    }

    protected function getUrl(Category $category): string
    {
        $link = FrontendLink::tag($category);
        return $link ? Html::tag('div', $link, ['class' => 'd-none d-md-block small']) : '';
    }

    protected function getCategoryAncestors(Category $category): string
    {
        if (!$category->parent_id) {
            return '';
        }

        $parents = [];

        foreach ($category->getAncestors() as $parent) {
            $parents[] = A::make()
                ->text($parent->name)
                ->href($this->getRoute($parent));
        }

        return implode(' / ', $parents);
    }

    protected function showCategoryAncestors(Category $category): bool
    {
        return $this->provider->searchString || $category->entryCategory;
    }

    protected function getCategoryDropdown(): ?FilterDropdown
    {
        return $this->provider->category
            ? FilterDropdown::make()
                ->items($this->getCategoryDropdownItems($this->provider->category))
                ->label($this->provider->category->getI18nAttribute('name'))
                ->param('id')
            : null;
    }

    protected function getCategoryDropdownItems(Category $category): array
    {
        $attribute = $this->model->getI18nAttributeName('name');
        return Category::indentNestedTree($category->getAncestors() + [$category], $attribute);
    }

    protected function initAncestors(): void
    {
        $categories = CategoryCollection::getAll();

        foreach ($this->provider->getModels() as $category) {
            $category->setAncestors($categories);
        }
    }
}
