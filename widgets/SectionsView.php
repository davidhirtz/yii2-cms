<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use yii\base\Widget;

/**
 * Class SectionsView
 * @package davidhirtz\yii2\cms\widgets
 */
class SectionsView extends Widget
{
    /**
     * @var Entry
     */
    public $entry;

    /**
     * @var Section[]
     */
    public $sections;

    /**
     * @var array containing additional view parameters.
     */
    public $viewParams = [];

    /**
     * @var string the path to the view file
     */
    public $viewFile = '_sections';

    /**
     * @var callable|null an anonymous function with the signature `function ($section)`, where `$section`
     * is the {@link Section} object that you can modify in the function.
     */
    public $isVisible;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->entry) {
            $this->sections = $this->entry->sections;
        }

        parent::init();
    }

    /**
     * Renders sections grouped by view file.
     */
    public function run()
    {
        $html = '';
        $prevViewFile = null;
        $sections = [];

        while ($section = current($this->sections)) {
            $viewFile = $this->getSectionViewFile($section);

            if ($prevViewFile != $viewFile) {
                if ($sections) {
                    $html .= $this->renderSectionsInternal($sections, $prevViewFile);
                    $sections = [];
                }
            }

            $sections[] = array_shift($this->sections);
            $prevViewFile = $viewFile;
        }

        if ($sections) {
            $html .= $this->renderSectionsInternal($sections, $prevViewFile);
        }

        return $html;
    }

    /**
     * Renders sections by type, removing them from the stack.
     *
     * @param array|int $types
     * @param string|null $viewFile
     */
    public function renderSectionsByType($types, $viewFile = null)
    {
        return $this->renderSectionsByCallback(function (Section $section) use ($types) {
            return in_array($section->type, (array)$types);
        }, $viewFile);
    }

    /**
     * @param callable $callback
     * @param null $viewFile
     */
    public function renderSectionsByCallback($callback, $viewFile = null)
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

        if ($sections) {
            $this->renderSectionsInternal($sections, $viewFile);
        }
    }

    /**
     * @param Section[] $sections
     * @param string|null $viewFile
     * @return string
     */
    protected function renderSectionsInternal($sections, $viewFile = null)
    {
        if ($viewFile === null) {
            $viewFile = $this->getSectionViewFile(current($sections));
        }

        if (is_callable($this->isVisible)) {
            $sections = array_filter($sections, $this->isVisible);
        }

        return !$viewFile ? '' : $this->render($viewFile, array_merge($this->viewParams, [
            'sections' => $sections,
        ]));
    }

    /**
     * @param Section $section
     * @return string
     */
    protected function getSectionViewFile($section)
    {
        return Section::getTypes()[$section->type]['viewFile'] ?? $this->viewFile;
    }

    /**
     * Override Widget::getViewPath() to set current controller's context.
     * @return array|string
     */
    public function getViewPath()
    {
        return \Yii::$app->controller->getViewPath();
    }
}