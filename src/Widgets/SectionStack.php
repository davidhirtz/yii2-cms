<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class SectionStack extends Widget
{
    protected Entry $entry;
    protected string $viewFile = '_sections';
    /**
     * @var array<string, mixed>
     */
    protected array $viewParams = [];

    /**
     * @var Section[]|null
     */
    protected ?array $sections = null;

    protected ?Closure $filter = null;
    protected ?Closure $groupKey = null;
    protected ?Closure $wrapperKey = null;
    protected ?Closure $sectionViewFile = null;
    protected ?Closure $wrapper = null;

    /**
     * @var SectionGroup[]
     */
    private array $groups = [];

    /**
     * @var Section[]
     */
    private array $orderedSections = [];

    /**
     * @var array<int, int>
     */
    private array $sectionOffsets = [];

    public function entry(Entry $entry): static
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @param Section[] $sections
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

    /**
     * @param Closure(Section): bool $filter
     */
    public function filter(Closure $filter): static
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @param Closure(Section): string $groupKey
     */
    public function groupKey(Closure $groupKey): static
    {
        $this->groupKey = $groupKey;
        return $this;
    }

    /**
     * @param Closure(Section): ?string $wrapperKey
     */
    public function wrapperKey(Closure $wrapperKey): static
    {
        $this->wrapperKey = $wrapperKey;
        return $this;
    }

    /**
     * @param Closure(Section): string $sectionViewFile
     */
    public function sectionViewFile(Closure $sectionViewFile): static
    {
        $this->sectionViewFile = $sectionViewFile;
        return $this;
    }

    /**
     * @param Closure(string, string, SectionGroup[]): (string|Stringable) $wrapper
     */
    public function wrapper(Closure $wrapper): static
    {
        $this->wrapper = $wrapper;
        return $this;
    }

    /**
     * An invisible stack runs no pipeline at all, so neither `filter()` nor a type's `visible` is consulted:
     * {@see Widget::render()} asks for the widget's own visibility only after `configure()`.
     */
    #[Override]
    protected function configure(): void
    {
        if ($this->isVisible()) {
            $this->sections ??= $this->entry->getVisibleSections();

            $this->groups = $this->createGroups($this->collectSections($this->filterSections($this->sections)));
            $this->setRenderOrder();
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $groups = [];
        $content = '';
        $key = null;
        $output = '';

        foreach ($this->groups as $group) {
            $html = $group->render();
            $wrapperKey = $group->getWrapperKey();

            if ($groups && $wrapperKey !== $key) {
                $output .= $this->renderWrapper($key, $content, $groups);
                $groups = [];
                $content = '';
            }

            $groups[] = $group;
            $content .= $html;
            $key = $wrapperKey;
        }

        return $groups ? $output . $this->renderWrapper($key, $content, $groups) : $output;
    }

    /**
     * The stack's relative view names resolve against the directory of the view that renders it, not against
     * `@views/<controller id>/`: the cms site views live in the bundle, where the application's view path does
     * not reach.
     */
    #[Override]
    public function getViewPath(): ?string
    {
        $viewFile = $this->view->getViewFile();
        return $this->viewPath ??= is_string($viewFile) ? dirname($viewFile) : parent::getViewPath();
    }

    /**
     * @return SectionGroup[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return Section[]
     */
    public function getSections(): array
    {
        return $this->orderedSections;
    }

    /**
     * @param Section $section
     * @return Section|null
     */
    public function getPrevious(Section $section): ?Section
    {
        return $this->getSectionByOffset($section, -1);
    }

    /**
     * @param Section $section
     * @return Section|null
     */
    public function getNext(Section $section): ?Section
    {
        return $this->getSectionByOffset($section, 1);
    }

    /**
     * @return Closure(Section, list<Section>): list<Section>
     */
    public static function collectAdjacent(): Closure
    {
        return static function (Section $head, array $rest): array {
            $sections = [];

            foreach ($rest as $section) {
                if ($section->type !== $head->type) {
                    break;
                }

                $sections[] = $section;
            }

            return $sections;
        };
    }

    /**
     * @return Closure(Section, list<Section>): list<Section>
     */
    public static function collectAll(): Closure
    {
        return static fn (Section $head, array $rest): array => array_values(
            array_filter($rest, fn (Section $section): bool => $section->type === $head->type)
        );
    }

    /**
     * @param Section[] $sections
     * @return Section[]
     */
    protected function filterSections(array $sections): array
    {
        $filtered = [];
        $position = 1;

        foreach ($sections as $section) {
            $visible = $section->getType()?->getVisible();

            $isVisible = $visible === null
                ? ($this->filter === null || ($this->filter)($section))
                : ($visible instanceof Closure ? $visible($section) : $visible);

            if ($isVisible) {
                $section->position = $position++;
                $filtered[] = $section;
            }
        }

        return $filtered;
    }

    /**
     * Collection is greedy and first come: an earlier head wins a section a later one would also take.
     *
     * @param Section[] $sections
     * @return array<int, array{sections: Section[], closed: bool}>
     */
    protected function collectSections(array $sections): array
    {
        $units = [];
        $taken = [];

        foreach ($sections as $offset => $section) {
            if (isset($taken[spl_object_id($section)])) {
                continue;
            }

            $collect = $section->getType()?->getCollect();
            $collected = [];

            if ($collect instanceof Closure) {
                $rest = array_filter(
                    array_slice($sections, $offset + 1),
                    fn (Section $current): bool => !isset($taken[spl_object_id($current)])
                );

                $collected = $collect($section, array_values($rest));

                foreach ($collected as $current) {
                    $taken[spl_object_id($current)] = true;
                }
            }

            $units[] = [
                'sections' => [$section, ...$collected],
                'closed' => (bool)$collected,
            ];
        }

        return $units;
    }

    /**
     * @param array<int, array{sections: Section[], closed: bool}> $units
     * @return SectionGroup[]
     */
    protected function createGroups(array $units): array
    {
        $groups = [];
        $current = null;

        foreach ($units as $unit) {
            $key = $this->getGroupKey($unit['sections'][0]);

            if ($current && ($unit['closed'] || $current['closed'] || $current['key'] !== $key)) {
                $groups[] = $this->createGroup($current['sections']);
                $current = null;
            }

            $current ??= [
                'sections' => [],
                'key' => $key,
                'closed' => $unit['closed'],
            ];

            $current['sections'] = [...$current['sections'], ...$unit['sections']];
        }

        if ($current) {
            $groups[] = $this->createGroup($current['sections']);
        }

        $previous = null;

        foreach ($groups as $group) {
            $group->previous($previous);
            $previous?->next($group);
            $previous = $group;
        }

        return $groups;
    }

    /**
     * @param Section[] $sections
     * @return SectionGroup
     */
    protected function createGroup(array $sections): SectionGroup
    {
        $head = $sections[0];

        return SectionGroup::make()
            ->stack($this)
            ->sections($sections)
            ->viewFile($this->getSectionViewFile($head))
            ->viewParams($this->viewParams)
            ->key($this->getGroupKey($head))
            ->wrapperKey($this->getWrapperKey($head));
    }

    /**
     * @param SectionGroup[] $groups
     */
    protected function renderWrapper(?string $key, string $content, array $groups): string|Stringable
    {
        if ($key === null) {
            return $content;
        }

        return $this->wrapper
            ? ($this->wrapper)($key, $content, $groups)
            : Div::make()
                ->class($key)
                ->content($content);
    }

    /**
     * @param Section $section
     */
    protected function getSectionViewFile(Section $section): string
    {
        return $this->sectionViewFile
            ? ($this->sectionViewFile)($section)
            : ($section->getType()?->getViewFile() ?: $this->viewFile);
    }

    /**
     * @param Section $section
     */
    protected function getGroupKey(Section $section): string
    {
        $key = $section->getType()?->getGroup();
        $key = $key instanceof Closure ? $key($section) : $key;

        $key ??= $this->groupKey
            ? ($this->groupKey)($section)
            : $this->getSectionViewFile($section);

        return (string)$key;
    }

    /**
     * @param Section $section
     */
    protected function getWrapperKey(Section $section): ?string
    {
        $key = $section->getType()?->getWrapper();
        $key = $key instanceof Closure ? $key($section) : $key;

        $key ??= $this->wrapperKey ? ($this->wrapperKey)($section) : null;

        return $key === '' ? null : $key;
    }

    private function setRenderOrder(): void
    {
        $this->orderedSections = [];
        $this->sectionOffsets = [];

        foreach ($this->groups as $group) {
            foreach ($group->getSections() as $section) {
                $this->sectionOffsets[spl_object_id($section)] = count($this->orderedSections);
                $this->orderedSections[] = $section;
            }
        }
    }

    /**
     * @param Section $section
     * @return Section|null
     */
    private function getSectionByOffset(Section $section, int $offset): ?Section
    {
        $current = $this->sectionOffsets[spl_object_id($section)] ?? null;

        return $current === null ? null : ($this->orderedSections[$current + $offset] ?? null);
    }
}
