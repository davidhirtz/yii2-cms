<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Interfaces\EntryRelationTypeInterface;
use yii\base\InvalidConfigException;

/**
 * @mixin EntryRelationTypeInterface
 */
trait EntryRelationTypeTrait
{
    protected bool $allowsEntries = true;

    /**
     * @var array<string, int>|null
     */
    protected ?array $entriesOrderBy = null;

    /**
     * @var list<int>
     */
    protected array $entriesTypes = [];

    public function allowEntries(bool $allowEntries = true): static
    {
        $this->allowsEntries = $allowEntries;
        return $this;
    }

    /**
     * @param array<string, int>|null $entriesOrderBy
     */
    public function entriesOrderBy(?array $entriesOrderBy): static
    {
        $this->entriesOrderBy = $entriesOrderBy;
        return $this;
    }

    public function entriesTypes(int ...$entriesTypes): static
    {
        $this->entriesTypes = array_values($entriesTypes);
        return $this;
    }

    public function allowsEntries(): bool
    {
        return $this->allowsEntries;
    }

    /**
     * @return array<string, int>|null
     */
    public function getEntriesOrderBy(): ?array
    {
        return $this->entriesOrderBy;
    }

    /**
     * @return list<int>|null
     */
    public function getEntriesTypes(): ?array
    {
        return $this->entriesTypes ?: null;
    }

    /**
     * @param class-string $modelClass
     */
    protected function validateEntriesTypes(string $modelClass): void
    {
        $entryTypes = Entry::instance()::getTypeDefinitions();

        foreach ($this->entriesTypes as $type) {
            if (!isset($entryTypes[$type])) {
                throw new InvalidConfigException("{$this->getDisplayValue()} of $modelClass names the undeclared entry type $type.");
            }
        }
    }
}
