<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Sets;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use yii\base\InvalidConfigException;

/**
 * A set of sections an entry can be given in one go, declared by `Module::$sectionSets`. It is born with its value
 * and must never be mutated afterwards: the module caches it and every use shares the instance.
 */
class SectionSet
{
    use ContainerConfigurationTrait;

    protected ?string $name = null;
    protected ?string $icon = null;
    protected Closure|bool $available = true;

    /**
     * @var list<SectionTemplate>
     */
    protected array $sections = [];

    public function __construct(public readonly int $value)
    {
    }

    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Whether the set is offered for an entry, and enforced by
     * {@see SectionController::actionCreateSet()} rather than only filtering the admin's select.
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

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getIcon(): string
    {
        return $this->icon ?? '';
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

    protected function getDisplayValue(): string
    {
        return static::class . ' "' . $this->value . '"';
    }
}
