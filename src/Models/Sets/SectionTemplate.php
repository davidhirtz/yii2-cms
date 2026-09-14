<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Sets;

use BackedEnum;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\IntBackedEnumTrait;

/**
 * One section of a {@see SectionSet}: the type it is created with, and the attribute values it starts out with.
 */
class SectionTemplate
{
    use ContainerConfigurationTrait;
    use IntBackedEnumTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    public readonly int $type;

    public function __construct(int|BackedEnum $type)
    {
        $this->type = static::getIntValue($type);
    }

    /**
     * Replaces what was declared before, {@see static::attribute()} adds to it.
     *
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function attribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
