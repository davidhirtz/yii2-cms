<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Models\Asset;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @template T of Asset
 */
class Gallery extends Widget
{
    use TagAttributesTrait;

    /**
     * @var T[]
     */
    protected ?array $assets = null;

    protected ?Closure $content = null;

    protected ?int $start = null;
    protected ?int $limit = null;
    protected string $viewFile = 'widgets/_assets';
    protected array $viewParams = [];

    protected array $viewports = [
        'hidden-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_MOBILE],
        'hidden block-sm' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_DESKTOP]
    ];

    private array $sharedViewports = [];

    public function assets(array $assets): static
    {
        $this->assets = $assets;
        return $this;
    }

    public function content(Closure $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function start(?int $start): static
    {
        $this->start = $start;
        return $this;
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function viewFile(?string $viewFile): static
    {
        $this->viewFile = $viewFile;
        return $this;
    }

    public function viewParams(array $viewParams): static
    {
        $this->viewParams = $viewParams;
        return $this;
    }

    #[Override]
    public function configure(): void
    {
        if ($this->viewports) {
            foreach ($this->viewports as $viewport) {
                $this->sharedViewports = $this->sharedViewports
                    ? array_intersect($this->sharedViewports, $viewport)
                    : $viewport;
            }
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $viewports = $this->getAssetsByViewports();
        $output = '';

        foreach ($viewports as $cssClass => $assets) {
            if (null !== $this->start || null !== $this->limit) {
                $assets = array_slice($assets, $this->start ?: 0, $this->limit);
            }

            $content = $this->renderAssetsInternal($assets);

            $output .= $cssClass || $this->attributes
                ? Div::make()
                    ->attributes($this->attributes)
                    ->addClass($cssClass)
                    ->content($content)
                : $content;
        }

        return $output;
    }

    /**
     * @param T[] $assets
     */
    protected function renderAssetsInternal(array $assets): string
    {
        if (!$assets) {
            return '';
        }

        return $this->content
            ? call_user_func($this->content, $assets)
            : $this->view->render($this->viewFile, [...$this->viewParams, 'assets' => $assets]);
    }

    /**
     * @return T[][]
     */
    protected function getAssetsByViewports(): array
    {
        $sameViewport = true;
        $viewports = [];

        if ($this->viewports) {
            foreach ($this->assets as $asset) {
                foreach ($this->viewports as $cssClass => $types) {
                    if (in_array($asset->type, $types, true)) {
                        $viewports[$cssClass][] = $asset;
                    }

                    if ($sameViewport && !in_array($asset->type, $this->sharedViewports, true)) {
                        $sameViewport = false;
                    }
                }
            }
        }

        return $sameViewport ? [null => $this->assets] : $viewports;
    }
}
