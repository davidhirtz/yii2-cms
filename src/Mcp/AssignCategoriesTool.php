<?php

declare(strict_types=1);

namespace Hirtz\Cms\Mcp;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Mcp\Tools\ResourceTool;
use Hirtz\Skeleton\Mcp\Tools\ToolException;
use Override;
use Throwable;
use Yii;

/**
 * Adds and removes rather than replacing the list: a category inheriting nested categories adds its ancestors, which a
 * caller replacing the list would remove again, and removing it removes its descendants.
 */
class AssignCategoriesTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'assign_categories';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Assign categories';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Adds an entry to categories and removes it from others, by their ids (find_records with resource '
            . '"category"). A category may add its parent categories as well. Returns the entry\'s categories.';
    }

    #[Override]
    public function getResources(): array
    {
        return array_values(array_filter(
            parent::getResources(),
            fn (Resource $resource): bool => !$resource->isReadOnly() && is_a($resource->getModelClass(), Entry::class, true),
        ));
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'id' => ['type' => ['integer', 'string']],
                'add' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'remove' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['resource', 'id'],
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    #[Override]
    public function isDestructive(): bool
    {
        return true;
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments, true);
        $entry = $this->findRecord($resource, $arguments['id']);

        if (!$entry instanceof Entry || !$entry->allowsCategories()) {
            throw new ToolException("This {$resource->getName()} takes no categories.");
        }

        /** @var list<mixed> $add */
        $add = $arguments['add'] ?? [];

        /** @var list<mixed> $remove */
        $remove = $arguments['remove'] ?? [];

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            foreach ($remove as $categoryId) {
                EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $categoryId])?->delete();
            }

            foreach ($add as $categoryId) {
                $this->addCategory($entry, is_int($categoryId) ? $categoryId : 0);
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        $entry->refresh();

        return [
            'record' => $this->getRecordSummary($entry),
            'categories' => array_map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->getAdminName(),
            ], array_values(Category::findAll(['id' => $entry->getCategoryIds()]))),
        ];
    }

    protected function addCategory(Entry $entry, int $categoryId): void
    {
        $category = Category::findOne($categoryId);

        if ($category === null) {
            throw new ToolException("Category not found: $categoryId");
        }

        if (in_array($category->id, $entry->getCategoryIds(), true)) {
            return;
        }

        $entryCategory = EntryCategory::create();
        $entryCategory->populateEntryRelation($entry);
        $entryCategory->populateCategoryRelation($category);

        if (!$entryCategory->insert()) {
            throw new ToolException("Category $categoryId cannot take this entry.", ['errors' => $entryCategory->getErrors()]);
        }

        $entry->refresh();
    }
}
