<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\base\Widget;

/**
 * SectionsView renders {@link Section} models. Sections will be rendered in their template set by `viewFile` or their
 * {@link Section::getViewFile()} method grouped by adjacent sections with the same template.
 *
 * @noinspection PhpUnused
 */
class SectionsView extends Widget
{
    /**
     * @var Entry|null
     */
    public ?Entry $entry = null;

    /**
     * @var Section[]|null
     */
    public ?array $sections = null;

    /**
     * @var array containing additional view parameters.
     */
    public array $viewParams = [];

    /**
     * @var string the path to the view file
     */
    public string $viewFile = '_sections';

    /**
     * @var callable|null an anonymous function with the signature `function ($section)`, where `$section` is the
     * {@link Section} object that you can modify in the function.
     */
    public mixed $isVisible = null;

    public function init(): void
    {
        $this->sections ??= $this->entry?->sections;
        parent::init();
    }

    /**
     * Renders sections grouped by view file.
     */
    public function run(): string
    {
        $html = '';
        $prevSection = null;
        $sections = [];

        foreach ($this->sections as $section) {
            if (!$this->hasSameViewFile($section, $prevSection)) {
                if ($sections) {
                    $html .= $this->renderSectionsInternal($sections, $this->getSectionViewFile($prevSection));
                    $sections = [];
                }
            }

            $sections[] = $section;
            $prevSection = $section;
        }

        if ($sections) {
            $html .= $this->renderSectionsInternal($sections, $this->getSectionViewFile($prevSection));
        }

        return $html;
    }

    /**
     * Renders adjacent sections by type starting with the given section. Rendered sections are then removed them from
     * the stack.
     *
     * @noinspection PhpUnused
     */
    public function renderAdjacentSectionsByType(Section $section, ?string $viewFile = null): string
    {
        $sections = [];

        foreach ($this->sections as $key => $current) {
            if ($section->id == $current->id || $sections) {
                if ($current->type != $section->type) {
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
     *
     * @noinspection PhpUnused
     */
    public function renderSectionsByType(array|int $types, ?string $viewFile = null): string
    {
        return $this->renderSectionsByCallback(fn(Section $section): bool => in_array($section->type, (array)$types), $viewFile);
    }

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

    protected function renderSectionsInternal(array $sections, ?string $viewFile = null): string
    {
        $viewFile ??= $this->getSectionViewFile(current($sections));

        if (is_callable($this->isVisible)) {
            $sections = array_filter($sections, $this->isVisible);
        }

        return !$viewFile || !$sections ? '' : $this->render($viewFile, [...$this->viewParams, 'sections' => $sections]);
    }

    protected function getSectionViewFile(Section $section): string
    {
        return $section->getViewFile() ?: $this->viewFile;
    }

    public function getViewPath(): ?string
    {
        return Yii::$app->controller->getViewPath();
    }

    protected function hasSameViewFile(Section $section, ?Section $prevSection): bool
    {
        return $prevSection && $this->getSectionViewFile($prevSection) == $this->getSectionViewFile($section);
    }
}