<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Helpers\FrontendLink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Stringable;
use Yii;

/**
 * @property CategoryActiveDataProvider $dataProvider
 */
trait CategoryGridTrait
{
    use TypeGridViewTrait;
    use ModuleTrait;

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make();
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property('name')
            ->property(Category::instance()->getI18nAttributeName('name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Category $category): string|Stringable
    {
        $name = $category->getI18nAttribute('name');

        $html = $name
            ? $this->search->markKeywords($name)
            : Yii::t('cms', '[ No title ]');

        $html = A::make()
            ->class($name ? 'strong' : 'strong text-muted')
            ->content($html)
            ->href($category->getAdminRoute());

        if ($this->showCategoryAncestors($category)) {
            $html .= Div::make()
                ->class('small', 'strong')
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
                ->href($parent->getAdminRoute());
        }

        return implode(' / ', $parents);
    }

    protected function showCategoryAncestors(Category $category): bool
    {
        return $this->provider->searchString || $category->entryCategory;
    }

    protected function initAncestors(): void
    {
        $categories = CategoryCollection::getAll();

        foreach ($this->provider->getModels() as $category) {
            $category->setAncestors($categories);
        }
    }
}
