<?php

declare(strict_types=1);

namespace Hirtz\Cms\Setup;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Setup\AssetSeederTrait;
use Hirtz\Skeleton\Setup\Seeder;
use Override;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Without a declaration, the entry is a homepage named after the application.
 */
class EntrySeeder extends Seeder
{
    use AssetSeederTrait;

    public array $dependencies = ['categories', 'folders'];

    #[Override]
    public function run(?array $value): void
    {
        if (Entry::find()->count()) {
            return;
        }

        foreach ($value ?? $this->getDefaultEntries() as $attributes) {
            $this->insertEntry((array)$attributes);
        }
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    protected function insertEntry(array $attributes, ?Entry $parent = null): void
    {
        $children = (array)ArrayHelper::remove($attributes, 'entries', []);
        $categories = (array)ArrayHelper::remove($attributes, 'categories', []);
        $sections = (array)ArrayHelper::remove($attributes, 'sections', []);
        $assets = (array)ArrayHelper::remove($attributes, 'assets', []);

        $entry = Entry::instantiateByType(self::getDeclaredType($attributes));
        $entry->setAttributes($attributes);

        if ($parent instanceof Entry) {
            $entry->populateRelation('parent', $parent);
            $entry->parent_id = $parent->id;
        }

        if (!$this->insert($entry)) {
            return;
        }

        $this->insertAssets($entry, $assets);

        foreach ($children as $child) {
            $this->insertEntry((array)$child, $entry);
        }

        foreach ($categories as $category) {
            $entryCategory = EntryCategory::create();
            $entryCategory->setAttributes((array)$category);
            $entryCategory->populateEntryRelation($entry);

            $this->insert($entryCategory);
        }

        foreach ($sections as $section) {
            $section = (array)$section;
            $assets = (array)ArrayHelper::remove($section, 'assets', []);

            $model = Section::instantiateByType(self::getDeclaredType($section));
            $model->setAttributes($section);
            $model->populateEntryRelation($entry);

            if ($this->insert($model)) {
                $this->insertAssets($model, $assets);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getDefaultEntries(): array
    {
        return [
            [
                'name' => 'Home',
                'slug' => Entry::getModule()->entryIndexSlug,
                'title' => Yii::$app->name,
            ],
        ];
    }
}
