<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Queries\CategoryQuery;

/**
 * The foreign key is deliberately not declared here: a trait `@property` is flattened into the using class, so a
 * second declaration of the same name there silently drops that class's whole PHPDoc scope instead of being
 * reported (monorepo issue #125). Each using model declares the column with its own nullability.
 *
 * @property-read Category|null $category {@see static::getCategory()}
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
