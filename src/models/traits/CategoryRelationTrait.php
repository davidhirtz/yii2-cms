<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;

/**
 * @property int|null $category_id
 * @property-read Category $category {@see static::getCategory()}
 */
trait CategoryRelationTrait
{
    public function getCategory(): CategoryQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function populateCategoryRelation(?Category $category): void
    {
        $this->populateRelation('category', $category);
        $this->category_id = $category?->id;
    }
}