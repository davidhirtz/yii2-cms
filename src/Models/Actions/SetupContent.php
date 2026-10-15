<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Closure;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Skeleton\Db\ActiveRecord;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Each insert is attempted on its own and a failed record is kept for the report, so one invalid declaration does
 * not cost the rest.
 */
class SetupContent
{
    /**
     * @var list<ActiveRecord>
     */
    private array $inserted = [];

    /**
     * @var list<ActiveRecord>
     */
    private array $failed = [];

    /**
     * @param list<array<string, mixed>> $categories
     * @param Closure(): list<array<string, mixed>>|list<array<string, mixed>> $entries a closure is called after the
     * categories are inserted, so an entry can name one it finds by slug
     */
    public function __construct(
        protected array $categories,
        protected Closure|array $entries,
    ) {
    }

    public function run(): bool
    {
        if ($this->categories && !Category::find()->count()) {
            $this->insertInTransaction($this->insertCategories(...));
        }

        if (!Entry::find()->count()) {
            $this->insertInTransaction($this->insertEntries(...));
        }

        FolderCollection::getDefault();

        return !$this->failed;
    }

    /**
     * @return list<ActiveRecord>
     */
    public function getInserted(): array
    {
        return $this->inserted;
    }

    /**
     * @return list<ActiveRecord>
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    protected function insertCategories(): void
    {
        foreach ($this->categories as $attributes) {
            $category = Category::instantiateByType(self::getDeclaredType($attributes));
            $category->setAttributes($attributes);

            $this->insert($category);
        }
    }

    protected function insertEntries(): void
    {
        $entries = $this->entries instanceof Closure ? ($this->entries)() : $this->entries;

        foreach ($entries as $attributes) {
            $this->insertEntry($attributes);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function insertEntry(array $attributes, ?Entry $parent = null): void
    {
        $children = ArrayHelper::remove($attributes, 'entries', []);
        $categories = ArrayHelper::remove($attributes, 'categories', []);
        $sections = ArrayHelper::remove($attributes, 'sections', []);

        $entry = Entry::instantiateByType(self::getDeclaredType($attributes));
        $entry->setAttributes($attributes);

        if ($parent instanceof Entry) {
            $entry->populateRelation('parent', $parent);
            $entry->parent_id = $parent->id;
        }

        if (!$this->insert($entry)) {
            return;
        }

        foreach ($children as $attributes) {
            $this->insertEntry($attributes, $entry);
        }

        foreach ($categories as $attributes) {
            $entryCategory = EntryCategory::create();
            $entryCategory->setAttributes($attributes);
            $entryCategory->populateEntryRelation($entry);

            $this->insert($entryCategory);
        }

        foreach ($sections as $attributes) {
            $section = Section::instantiateByType(self::getDeclaredType($attributes));
            $section->setAttributes($attributes);
            $section->populateEntryRelation($entry);

            $this->insert($section);
        }
    }

    protected function insert(ActiveRecord $record): bool
    {
        if ($record->insert()) {
            $this->inserted[] = $record;
            return true;
        }

        $this->failed[] = $record;
        return false;
    }

    protected function insertInTransaction(Closure $callback): void
    {
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            $callback();
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected static function getDeclaredType(array $attributes): ?int
    {
        return isset($attributes['type']) ? (int)$attributes['type'] : null;
    }
}
