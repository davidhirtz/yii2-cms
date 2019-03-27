<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class AssetsView.
 * @package davidhirtz\yii2\cms\widgets
 */
class AssetsView extends Widget
{
    /**
     * @var Asset[]
     */
    public $assets;

    /**
     * @var string
     */
    public $viewFile = '_assets';

    /**
     * @var array
     */
    public $viewPath;

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var array containing css class as key and related asset type as value.
     */
    public $breakpoints = [];

    /**
     * @var array containing asset type as key and an array of shared types as value.
     */
    public $sharedBreakpoints = [];

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var string slider class that will be applied if there is more than one item.
     */
    public $sliderClass = 'slider';

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Find shared breakpoints, this helps to determine whether we need multiple
        // views for the breakpoints
        if (!$this->sharedBreakpoints && $this->breakpoints) {
            foreach ($this->breakpoints as $breakpoint) {
                $this->sharedBreakpoints = $this->sharedBreakpoints ? array_intersect($this->sharedBreakpoints, $breakpoint) : $breakpoint;
            }
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $sameViewport = true;
        $breakpoints = [];
        $output = '';

        if ($this->breakpoints) {
            foreach ($this->assets as $asset) {
                foreach ($this->breakpoints as $cssClass => $types) {
                    if (in_array($asset->type, $types)) {
                        $breakpoints[$cssClass][] = $asset;
                    }

                    if ($sameViewport && !in_array($asset->type, $this->sharedBreakpoints)) {
                        $sameViewport = false;
                    }
                }
            }
        }

        if ($sameViewport) {
            $breakpoints = [$this->assets];
        }

        foreach ($breakpoints as $cssClass => $assets) {
            if ($this->start !== null || $this->limit !== null) {
                $assets = array_slice($assets, (int)$this->start, $this->limit);
            }

            if ($assets) {
                $content = Yii::$app->getView()->render($this->viewFile, [
                    'assets' => $assets,
                ]);

                $options = $this->prepareOptions($this->options, $assets);

                if (is_string($cssClass)) {
                    Html::addCssClass($options, $cssClass);
                }

                $output .= Html::tag('div', $content, $options);
            }
        }

        return $output;
    }

    /**
     * @param array $options
     * @param Asset[] $assets
     * @return array
     */
    protected function prepareOptions($options, $assets)
    {
        if (count($assets) > 1 && $this->sliderClass) {
            Html::addCssClass($options, $this->sliderClass);
        }

        return $options;
    }

    /**
     * @return array|string
     */
    public function getViewPath()
    {
        return $this->viewPath === null ? Yii::$app->controller->getViewPath() : $this->viewPath;
    }
}