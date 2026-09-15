<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Menus;

use BackedEnum;
use Closure;
use Hirtz\Cms\Models\Collections\MenuCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Models\Definitions\Definition;
use yii\base\InvalidConfigException;

/**
 * A menu an entry can be put into, declared by `Module::$menus`. It is a plain `Definition` rather than a
 * `ModelDefinition`: no model declares it and none is validated against.
 */
class Menu extends Definition
{
    protected Closure|bool $available = true;
    protected bool $autoload = true;

    /**
     * Whether the menu is offered for an entry. A menu an entry is already stored in stays offered and stays
     * valid, see {@see static::isAvailableOrStored()}.
     *
     * @param Closure(Entry): bool|bool $available
     */
    public function available(Closure|bool $available = true): static
    {
        $this->available = $available;
        return $this;
    }

    /**
     * Whether {@see MenuCollection} loads the menu with the others, in the one query a layout pays for. A menu
     * that is rarely rendered, or holds far more entries than a navigation, is loaded on its own instead.
     */
    public function autoload(bool $autoload = true): static
    {
        $this->autoload = $autoload;
        return $this;
    }

    /**
     * A menu is addressed by its value, and a project declaring its menus as an enum addresses them by the case.
     */
    public static function getValue(int|BackedEnum $menu): int
    {
        return static::getIntValue($menu);
    }

    public function isAvailable(Entry $entry): bool
    {
        return $this->available instanceof Closure ? (bool)($this->available)($entry) : $this->available;
    }

    public function isAvailableOrStored(Entry $entry): bool
    {
        return $this->isAvailable($entry)
            || in_array($this->value, $entry->getOldMenuIds(), true);
    }

    public function isAutoloaded(): bool
    {
        return $this->autoload;
    }

    public function validate(): void
    {
        if ($this->getName() === '') {
            throw new InvalidConfigException("{$this->getDisplayValue()} has no name.");
        }
    }
}
