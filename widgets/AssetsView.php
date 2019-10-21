<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
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
     * @var array containing additional view parameters.
     */
    public $params = [];

    /**
     * @var string
     */
    public $viewFile = '_assets';

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var array containing CSS class as key and related asset types as value.
     *
     * [
     *     'hidden-md' => [Asset::TYPE_MOBILE],
     *     'hidden block-md' => [Asset::TYPE_TABLET, Asset::TYPE_DESKTOP],
     * ]
     */
    public $breakpoints = [];

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var string slider class that will be applied if there is more than one item.
     */
    public $sliderClass = 'slider';

    /**
     * @var array containing asset type as key and an array of shared types as value.
     */
    protected $sharedBreakpoints = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->breakpoints) {
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
                $this->params['assets'] = $assets;
                $content = $this->render($this->viewFile, $this->params);

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
}