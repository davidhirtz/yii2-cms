<?php

declare(strict_types=1);

namespace Hirtz\Cms\Setup;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Setup\Seeder;
use Override;

class CategorySeeder extends Seeder
{
    #[Override]
    public function run(?array $value): void
    {
        if (!$value || Category::find()->count()) {
            return;
        }

        foreach ($value as $attributes) {
            $category = Category::instantiateByType(self::getDeclaredType((array)$attributes));
            $category->setAttributes((array)$attributes);

            $this->insert($category);
        }
    }
}
