<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\base\Widget;

/**
 * Class SectionsView.
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
     * @var array
     */
    public $viewParams = [];

    /**
     * @var string
     */
    public $defaultViewFile = '_sections';

    /**
     * @var array
     */
    public $viewPath;

    /**
     * Init.
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
     * @param string $viewFile
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
                if (!$viewFile) {
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
     * @param string $viewFile
     * @return string
     */
    protected function renderSectionsInternal($sections, $viewFile = null)
    {
        if (!$viewFile) {
            $viewFile = $this->getSectionViewFile(current($sections));
        }

        return $this->render($viewFile, array_merge($this->viewParams, [
            'sections' => $sections,
        ]));
    }

    /**
     * @param Section $section
     * @return string
     */
    protected function getSectionViewFile($section)
    {
        return isset(Section::getTypes()[$section->type]['viewFile']) ? Section::getTypes()[$section->type]['viewFile'] : $this->defaultViewFile;
    }

    /**
     * @return array|string
     */
    public function getViewPath()
    {
        return $this->viewPath === null ? Yii::$app->controller->getViewPath() : $this->viewPath;
    }
}