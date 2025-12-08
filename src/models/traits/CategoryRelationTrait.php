<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\traits;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\queries\CategoryQuery;

/**
 * @property int|null $category_id
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
