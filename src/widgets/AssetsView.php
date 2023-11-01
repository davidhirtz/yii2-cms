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
     * @var int number of assets to skip
     */
    public $start;

    /**
     * @var int number of assets to show
     */
    public $limit;

    /**
     * @var array containing additional view parameters.
     */
    public $viewParams = [];

    /**
     * @var string
     */
    public $viewFile = '_assets';

    /**
     * @var array
     */
    public $wrapperOptions = [];

    /**
     * @var array containing CSS class as key and related asset types as value.
     *
     * [
     *     'hidden-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_MOBILE],
     *     'hidden block-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_DESKTOP],
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
                $assets = array_slice($assets, $this->start ?: 0, $this->limit);
            }

            $content = $this->renderAssetsInternal($assets);
            $wrapperOptions = $this->wrapperOptions;

            if (is_string($cssClass)) {
                Html::addCssClass($wrapperOptions, $cssClass);
            }

            $output .= $wrapperOptions ? Html::tag('div', $content, $wrapperOptions) : $content;
        }

        return $output;
    }

    /**
     * @param Asset[] $assets
     * @return string
     */
    protected function renderAssetsInternal($assets)
    {
        if ($assets) {
            $content = $this->render($this->viewFile, [...$this->viewParams, 'assets' => $assets]);
            $options = $this->prepareOptions($this->options, $assets);

            return $options ? Html::tag('div', $content, $options) : $content;
        }

        return '';
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
     * @return array|false
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