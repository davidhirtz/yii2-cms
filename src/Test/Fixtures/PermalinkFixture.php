<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Override;
use yii\db\Expression;
use yii\test\ActiveFixture;

/**
 * Fixtures insert rows straight into the table, so no model event fires and no permalink is written. This derives
 * them from the entry fixture data instead of repeating the slugs, which keeps the two from drifting apart.
 */
class PermalinkFixture extends ActiveFixture
{
    public $modelClass = Permalink::class;

    public $depends = [
        EntryFixture::class,
    ];

    #[Override]
    protected function getData(): array
    {
        $entry = TestEntry::instance();
        $entries = require(__DIR__ . '/Data/entry.php');

        $now = new Expression('UTC_TIMESTAMP()');
        $data = [];

        foreach ($entry->getI18nAttributeNames('slug') as $language => $slugAttribute) {
            $parentSlugAttribute = $entry->getI18nAttributeName('parent_slug', $language);

            foreach ($entries as $key => $attributes) {
                $slug = $attributes[$slugAttribute] ?? null;

                if (!$slug) {
                    continue;
                }

                $prefix = (string)($attributes[$parentSlugAttribute] ?? '');

                $data["$key-$language"] = [
                    'language' => $language,
                    'uri' => trim("$prefix/$slug", '/'),
                    'slug' => $slug,
                    'model' => TestEntry::class,
                    'model_id' => $attributes['id'],
                    'created_at' => $now,
                ];
            }
        }

        return $data;
    }
}
