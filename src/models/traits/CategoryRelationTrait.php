<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;

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
