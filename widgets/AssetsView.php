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
     * @var array containing additional view parameters.
     */
    public $viewParams = [];

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
    public $viewports = [];

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
    protected $sharedViewports = [];

    /**
     * Finds assets types that are represented in more than one viewport.
     */
    public function init()
    {
        if ($this->viewports) {
            foreach ($this->viewports as $viewport) {
                $this->sharedViewports = $this->sharedViewports ? array_intersect($this->sharedViewports, $viewport) : $viewport;
            }
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $viewports = $this->getAssetsByViewports();
        $output = '';

        foreach ($viewports as $cssClass => $assets) {
            if ($this->start !== null || $this->limit !== null) {
                $assets = array_slice($assets, (int)$this->start, $this->limit);
            }

            if ($assets) {
                $this->viewParams['assets'] = $assets;
                $content = $this->render($this->viewFile, $this->viewParams);
                $options = $this->prepareOptions($this->options, $assets);

                $content = Html::tag('div', $content, $options);
                $output .= is_string($cssClass) ? Html::tag('div', $content, ['class' => $cssClass]) : $content;
            }
        }

        return $output;
    }

    /**
     * @return Asset[][]
     */
    public function getAssetsByViewports()
    {
        $sameViewport = true;
        $viewports = [];

        if ($this->viewports) {
            foreach ($this->assets as $asset) {
                foreach ($this->viewports as $cssClass => $types) {
                    if (in_array($asset->type, $types)) {
                        $viewports[$cssClass][] = $asset;
                    }

                    if ($sameViewport && !in_array($asset->type, $this->sharedViewports)) {
                        $sameViewport = false;
                    }
                }
            }
        }

        return $sameViewport ? [$this->assets] : $viewports;
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
     * Override Widget::getViewPath() to set current controller's context.
     * @return array|string
     */
    public function getViewPath()
    {
        return Yii::$app->controller->getViewPath();
    }
}