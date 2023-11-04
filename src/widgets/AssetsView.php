<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class AssetsView extends Widget
{
    /**
     * @var Asset[]
     */
    public ?array $assets = null;

    public ?int $start = null;
    public ?int $limit = null;
    public array $viewParams = [];
    public string $viewFile = '_assets';

    public array $options = [];
    public array $wrapperOptions = [];

    public array $viewports = [
        'hidden-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_MOBILE],
        'hidden block-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_DESKTOP]
    ];

    protected array $sharedViewports = [];

    public function init(): void
    {
        if ($this->viewports) {
            foreach ($this->viewports as $viewport) {
                $this->sharedViewports = $this->sharedViewports ? array_intersect($this->sharedViewports, $viewport) : $viewport;
            }
        }

        parent::init();
    }

    public function run(): string
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

    protected function renderAssetsInternal(array $assets): string
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
    public function getAssetsByViewports(): array
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
     * @param Asset[] $assets
     * @noinspection PhpUnusedParameterInspection
     */
    protected function prepareOptions(array $options, array $assets = []): array
    {
        return $options;
    }

    public function getViewPath(): ?string
    {
        return Yii::$app->controller->getViewPath();
    }
}