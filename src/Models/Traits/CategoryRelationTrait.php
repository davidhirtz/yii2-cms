<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Queries\CategoryQuery;

/**
 * `category_id` is not declared here, since a trait `@property` the using class declares too drops its whole
 * PHPDoc scope (#125).
 *
 * @property-read Category|null $category
 */
trait CategoryRelationTrait
{
    public function getCategory(): CategoryQuery
    {
        /** @var CategoryQuery $relation */
        $relation = $this->hasOne(Category::class, ['id' => 'category_id']);
        return $relation;
    }

    public function populateCategoryRelation(?Category $category): void
    {
        $this->populateRelation('category', $category);
        $this->category_id = $category?->id;
    }
}
