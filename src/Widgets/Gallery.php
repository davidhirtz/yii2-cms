<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Widgets\Traits\TailwindViewportsTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @template T of Asset = Asset
 */
class Gallery extends Widget
{
    use TailwindViewportsTrait;
    use TagAttributesTrait;

    /**
     * @var list<T>|null
     */
    protected ?array $assets = null;

    protected ?int $start = null;
    protected ?int $limit = null;

    /**
     * @var list<Closure>|null
     */
    private ?array $artworkClosures = null;

    /**
     * The markup of a run of assets, set by `content()` or `viewFile()`, whichever was called last.
     */
    private ?Closure $content = null;

    /**
     * @var list<int>
     */
    private array $sharedViewports = [];

    /**
     * @param Closure(Artwork): Artwork $artwork
     */
    public function artwork(Closure $artwork): static
    {
        $this->artworkClosures[] = $artwork;
        return $this;
    }

    /**
     * @param list<T> $assets
     */
    public function assets(array $assets): static
    {
        $this->assets = $assets;
        return $this;
    }

    /**
     * @param Closure(list<T> $assets, static $gallery): string $content
     */
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

    /**
     * The view receives `$assets` (`list<T>`) and `$gallery` beside the `$params`.
     *
     * @param array<string, mixed> $params
     */
    public function viewFile(?string $viewFile, array $params = []): static
    {
        $this->content = $viewFile
            ? fn (array $assets): string => $this->view->render($viewFile, [
                ...$params,
                'assets' => $assets,
                'gallery' => $this,
            ])
            : null;

        return $this;
    }

    /**
     * What the gallery renders per asset unless `content()` or `viewFile()` take over, which call it themselves to
     * keep the caller's `artwork()` closures.
     *
     * @param T $asset
     */
    public function makeArtwork(Asset $asset): Artwork
    {
        return $this->evaluate($this->artworkClosures, Artwork::make()->asset($asset));
    }

    #[Override]
    public function configure(): void
    {
        if ($this->viewports) {
            foreach ($this->viewports as $viewport) {
                $this->sharedViewports = $this->sharedViewports
                    ? array_values(array_intersect($this->sharedViewports, $viewport))
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
     * @param list<T> $assets
     */
    protected function renderAssetsInternal(array $assets): string
    {
        if (!$assets) {
            return '';
        }

        return $this->content
            ? ($this->content)($assets, $this)
            : implode('', array_map($this->makeArtwork(...), $assets));
    }

    /**
     * @return array<string, list<T>>
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

        return $sameViewport ? ['' => $this->assets] : $viewports;
    }
}
