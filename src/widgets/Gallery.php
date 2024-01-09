<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\Widget;

/**
 * @template T of Asset
 */
class Gallery extends Widget
{
    /**
     * @var T[]
     */
    public ?array $assets = null;

    public ?int $start = null;
    public ?int $limit = null;
    public array $viewParams = [];

    public array $options = [];
    public array $wrapperOptions = [];

    public array $viewports = [
        'hidden-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_MOBILE],
        'hidden block-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_DESKTOP]
    ];

    public string $viewFile = 'widgets/_assets';

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

            if ($content) {
                $wrapperOptions = $this->wrapperOptions;

                if (is_string($cssClass)) {
                    Html::addCssClass($wrapperOptions, $cssClass);
                }

                $output .= $wrapperOptions ? Html::tag('div', $content, $wrapperOptions) : $content;
            }
        }

        return $output;
    }

    /**
     * @param T[] $assets
     */
    protected function renderAssetsInternal(array $assets): string
    {
        if ($assets) {
            $content = $this->getView()->render($this->viewFile, [...$this->viewParams, 'assets' => $assets], $this);
            $options = $this->prepareOptions($this->options, $assets);

            return $options ? Html::tag('div', $content, $options) : $content;
        }

        return '';
    }

    /**
     * @return T[][]
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
     * @param T[] $assets
     * @noinspection PhpUnusedParameterInspection
     */
    protected function prepareOptions(array $options, array $assets = []): array
    {
        return $options;
    }
}
