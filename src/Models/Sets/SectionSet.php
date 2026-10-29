<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Sets;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Skeleton\Models\Definitions\Definition;
use yii\base\InvalidConfigException;

/**
 * A plain `Definition`, since no model declares a set and none is validated against.
 */
class SectionSet extends Definition
{
    protected Closure|bool $available = true;

    /**
     * @var list<SectionTemplate>
     */
    protected array $sections = [];

    /**
     * Enforced by {@see SectionController::actionCreateSet()}, not only by the admin's select.
     *
     * @param Closure(Entry): bool|bool $available
     */
    public function available(Closure|bool $available = true): static
    {
        $this->available = $available;
        return $this;
    }

    public function sections(SectionTemplate ...$sections): static
    {
        $this->sections = array_values($sections);
        return $this;
    }

    /**
     * @return list<SectionTemplate>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function isAvailable(Entry $entry): bool
    {
        return $this->available instanceof Closure ? (bool)($this->available)($entry) : $this->available;
    }

    public function validate(): void
    {
        if ($this->getName() === '') {
            throw new InvalidConfigException("{$this->getDisplayValue()} has no name.");
        }

        if (!$this->sections) {
            throw new InvalidConfigException("{$this->getDisplayValue()} declares no sections.");
        }

        $types = Section::instance()::getTypeDefinitions();

        foreach ($this->sections as $section) {
            if (!isset($types[$section->type])) {
                throw new InvalidConfigException("{$this->getDisplayValue()} names the undeclared section type $section->type.");
            }
        }
    }
}
