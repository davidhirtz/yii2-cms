<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @template T of Section
 */
class SectionGroup extends Widget
{
    /**
     * @var SectionStack<T>
     */
    protected SectionStack $stack;

    /**
     * @var T[]
     */
    protected array $sections = [];

    protected string $viewFile = '';
    /**
     * @var array<string, mixed>
     */
    protected array $viewParams = [];
    protected string $key = '';
    protected ?string $wrapperKey = null;

    /**
     * @var SectionGroup<T>|null
     */
    protected ?SectionGroup $previous = null;

    /**
     * @var SectionGroup<T>|null
     */
    protected ?SectionGroup $next = null;

    /**
     * @var T[]
     */
    private array $remaining = [];

    /**
     * @param SectionStack<T> $stack
     */
    public function stack(SectionStack $stack): static
    {
        $this->stack = $stack;
        return $this;
    }

    /**
     * @param T[] $sections
     */
    public function sections(array $sections): static
    {
        $this->sections = $sections;
        return $this;
    }

    public function viewFile(string $viewFile): static
    {
        $this->viewFile = $viewFile;
        return $this;
    }

    /**
     * @param array<string, mixed> $viewParams
     */
    public function viewParams(array $viewParams): static
    {
        $this->viewParams = $viewParams;
        return $this;
    }

    public function key(string $key): static
    {
        $this->key = $key;
        return $this;
    }

    public function wrapperKey(?string $wrapperKey): static
    {
        $this->wrapperKey = $wrapperKey;
        return $this;
    }

    /**
     * @param SectionGroup<T>|null $previous
     */
    public function previous(?SectionGroup $previous): static
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * @param SectionGroup<T>|null $next
     */
    public function next(?SectionGroup $next): static
    {
        $this->next = $next;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->remaining = $this->sections;
        parent::configure();
    }

    /**
     * The group is the view's `$context`, which is what makes `$context->renderAdjacent()` and a relative view
     * name work inside it.
     */
    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->viewFile || !$this->sections) {
            return '';
        }

        return $this->view->render($this->viewFile, [
            ...$this->viewParams,
            'sections' => $this->sections,
            'group' => $this,
        ], $this);
    }

    #[Override]
    public function getViewPath(): ?string
    {
        return $this->viewPath ?? $this->stack->getViewPath();
    }

    /**
     * @param T $section
     */
    public function renderAdjacent(Section $section, ?string $viewFile = null): string
    {
        $sections = [];

        foreach ($this->remaining as $offset => $current) {
            if ($current === $section || $sections) {
                if ($current->type !== $section->type) {
                    break;
                }

                $sections[] = $current;
                unset($this->remaining[$offset]);
            }
        }

        return $this->renderSections($sections, $viewFile);
    }

    /**
     * @param Closure(T): bool $callback
     */
    public function renderWhere(Closure $callback, ?string $viewFile = null): string
    {
        $sections = [];

        foreach ($this->remaining as $offset => $current) {
            if ($callback($current)) {
                $sections[] = $current;
                unset($this->remaining[$offset]);
            }
        }

        return $this->renderSections($sections, $viewFile);
    }

    /**
     * @return T[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function getViewFile(): string
    {
        return $this->viewFile;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getWrapperKey(): ?string
    {
        return $this->wrapperKey;
    }

    /**
     * @return SectionStack<T>
     */
    public function getStack(): SectionStack
    {
        return $this->stack;
    }

    /**
     * @return SectionGroup<T>|null
     */
    public function getPrevious(): ?SectionGroup
    {
        return $this->previous;
    }

    /**
     * @return SectionGroup<T>|null
     */
    public function getNext(): ?SectionGroup
    {
        return $this->next;
    }

    /**
     * @param T[] $sections
     */
    protected function renderSections(array $sections, ?string $viewFile = null): string
    {
        if (!$sections) {
            return '';
        }

        return (string)static::make()
            ->stack($this->stack)
            ->sections($sections)
            ->viewFile($viewFile ?? $this->viewFile)
            ->viewParams($this->viewParams)
            ->key($this->key)
            ->wrapperKey($this->wrapperKey);
    }
}
