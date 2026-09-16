<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\FrontendLink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Stringable;
use Yii;

/**
 * @property CategoryActiveDataProvider $dataProvider
 */
trait CategoryGridTrait
{
    use ModuleTrait;

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make();
    }

    protected function getTypeColumn(): ?Column
    {
        return $this->provider->type === null
            ? TypeColumn::make()
                ->url($this->getRecordUrl(...))
                ->visible($this->hasVisibleTypes())
            : null;
    }

    protected function hasVisibleTypes(): bool
    {
        return count(Category::instance()::getTypeDefinitions()) > 1;
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
            : Yii::t('cms', 'COMMON_NO_TITLE');

        $url = $this->getRecordUrl($category);
        $class = $name ? 'strong' : 'strong text-muted';

        $html = $url
            ? A::make()->class($class)->content($html)->href($url)
            : Div::make()->class($class)->content($html);

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

    /**
     * Whether the grid is a list to pick a category *from* rather than to navigate. A picker must not lead away
     * from itself — that cancels the flow the user is in — so its rows drill into the subcategories instead, its
     * entry count badge carries no link, and the category's own page is an external link button.
     */
    protected function isPicker(): bool
    {
        return false;
    }

    /**
     * Where the row's own links lead: the name, the type icon and the ancestors.
     *
     * @return array<array-key, mixed>|string|null
     */
    protected function getRecordUrl(Category $category): array|string|null
    {
        if (!$this->isPicker()) {
            return $category->getAdminRoute();
        }

        return $this->hasBranchesEnabled() && $category->getBranchCount()
            ? $this->getBranchUrl($category)
            : null;
    }

    protected function getBranchCountColumn(): ?Column
    {
        if (!$this->hasBranchesEnabled()) {
            return null;
        }

        return BadgeColumn::make()
            ->property('branchCount')
            ->url($this->getBranchUrl(...));
    }

    protected function hasBranchesEnabled(): bool
    {
        return $this->provider->parent?->allowsDescendants()
            ?? static::getModule()->enableNestedCategories;
    }

    /**
     * @return array<array-key, mixed>|string
     */
    protected function getBranchUrl(Category $category): array|string
    {
        return Url::current([
            $this->categoryParamName => $category->id,
            'page' => null,
            'q' => null,
        ]);
    }

    protected function getEntryCountColumn(): ?Column
    {
        return BadgeColumn::make()
            ->property('entry_count')
            ->url($this->isPicker() ? null : fn (Category $category) => ['entry/index', 'category' => $category->id])
            ->value(fn (Category $category) => $category->allowsEntries() ? $category->entry_count : null);
    }

    protected function getUrl(Category $category): string|Stringable
    {
        $link = (string)FrontendLink::make()->model($category);
        return $link ? Div::make()->content($link)->class('d-none d-md-block small') : '';
    }

    protected function getCategoryAncestors(Category $category): string
    {
        if (!$category->parent_id) {
            return '';
        }

        $parents = [];

        foreach ($category->getAncestors() as $parent) {
            $url = $this->getRecordUrl($parent);
            $name = (string)$parent->getI18nAttribute('name', fallback: true);

            $parents[] = $url
                ? A::make()->text($name)->href($url)
                : Html::encode($name);
        }

        return implode(' / ', $parents);
    }

    protected function showCategoryAncestors(Category $category): bool
    {
        return (bool)$category->parent_id && ($this->provider->searchString || $category->entryCategory);
    }

    protected function initAncestors(): void
    {
        $categories = CategoryCollection::getAll();

        foreach ($this->provider->getModels() as $category) {
            $category->setAncestors($categories);
        }
    }
}
