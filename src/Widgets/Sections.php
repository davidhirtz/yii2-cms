<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @template T of Section
 */
class Sections extends Widget
{
    protected Entry $entry;
    protected string $viewFile = '_sections';
    protected array $viewParams = [];
    protected ?Closure $visible = null;

    /**
     * @var T[]
     */
    protected array $sections;

    /**
     * @var T[][]
     */
    protected array $groups;

    public function entry(Entry $entry): static
    {
        $this->entry = $entry;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->sections ??= $this->entry->sections ?? [];

        $this->prepareSections();
        $this->createSectionGroups();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return implode('', $this->groups);
    }

    protected function prepareSections(): void
    {
        $sections = [];
        $position = 1;

        foreach ($this->sections as $section) {
            $visible = $section->getTypeOptions()['visible'] ?? $this->visible;

            if (is_callable($visible) ? call_user_func($visible, $section) : ($visible ?? true)) {
                $section->position = $position++;
                $sections[] = $section;
            }
        }

        $this->sections = $sections;
    }

    protected function createSectionGroups(): void
    {
        $prevSection = null;
        $this->groups = [];
        $sections = [];

        foreach ($this->sections as $section) {
            if (!$this->hasSameViewFile($section, $prevSection)) {
                if ($sections) {
                    $this->groups[] = $this->renderSectionsInternal($sections, $this->getSectionViewFile($prevSection));
                    $sections = [];
                }
            }

            $sections[] = $section;
            $prevSection = $section;
        }

        if ($sections) {
            $this->groups[] = $this->renderSectionsInternal($sections, $this->getSectionViewFile($prevSection));
        }
    }

    /**
     * Renders adjacent sections by type starting with the given section and removes them from the stack.
     * @noinspection PhpUnused
     */
    public function renderAdjacentSectionsByType(Section $section, ?string $viewFile = null): string
    {
        $sections = [];

        foreach ($this->sections as $key => $current) {
            if ($section->id === $current->id || $sections) {
                if ($current->type !== $section->type) {
                    break;
                }

                $sections[] = $current;
                unset($this->sections[$key]);
            }
        }

        return $sections ? $this->renderSectionsInternal($sections, $viewFile) : '';
    }

    /**
     * Renders sections by type, removing them from the stack.
     * @noinspection PhpUnused
     */
    public function renderSectionsByType(array|int $types, ?string $viewFile = null): string
    {
        return $this->renderSectionsByCallback(fn (Section $section): bool => in_array($section->type, (array)$types, true), $viewFile);
    }

    /**
     * Renders sections by callback, removing them from the stack.
     * @noinspection PhpUnused
     */
    public function renderSectionsByCallback(callable $callback, ?string $viewFile = null): string
    {
        $sections = [];

        foreach ($this->sections as $key => $section) {
            if (call_user_func($callback, $section, $sections)) {
                if ($viewFile === null) {
                    $viewFile = $this->getSectionViewFile($section);
                }

                $sections[] = $this->sections[$key];
                unset($this->sections[$key]);
            }
        }

        return $sections ? $this->renderSectionsInternal($sections, $viewFile) : '';
    }

    /**
     * @param T[] $sections
     */
    protected function renderSectionsInternal(array $sections, ?string $viewFile = null): string
    {
        $viewFile ??= $this->getSectionViewFile(current($sections));

        return $viewFile && $sections
            ? $this->view->render($viewFile, [...$this->viewParams, 'sections' => $sections])
            : '';
    }

    /**
     * @param T $section
     */
    protected function getSectionViewFile(Section $section): string
    {
        return $section->getViewFile() ?: $this->viewFile;
    }

    /**
     * @param T $section
     * @param T|null $prevSection
     */
    protected function hasSameViewFile(Section $section, ?Section $prevSection): bool
    {
        return $prevSection && $this->getSectionViewFile($prevSection) === $this->getSectionViewFile($section);
    }
}
