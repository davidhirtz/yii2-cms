<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Hirtz\Cms\Models\Interfaces\EntryRelationTypeInterface;
use Hirtz\Cms\Models\Types\Traits\EntryRelationTypeTrait;
use Hirtz\Media\Models\Interfaces\AssetModelTypeInterface;
use Hirtz\Media\Models\Types\Traits\AssetModelTypeTrait;
use Override;

/**
 * A block is rendered wherever a section points at it, so it declares its own view file and nothing about where
 * that sits in the stack — `group`, `wrapper` and `collect` stay on {@see SectionType}.
 */
class BlockType extends Type implements AssetModelTypeInterface, EntryRelationTypeInterface
{
    // The class's own `validate()` would shadow the trait's, so it is aliased and called from there instead.
    use AssetModelTypeTrait {
        validate as validateAssetModelType;
    }

    use EntryRelationTypeTrait;

    #[Override]
    public function validate(string $modelClass): void
    {
        $this->validateAssetModelType($modelClass);
        $this->validateEntriesTypes($modelClass);
    }
}
