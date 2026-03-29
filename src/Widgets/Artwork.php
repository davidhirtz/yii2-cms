<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Helpers\Html;
use Hirtz\Media\Widgets\Media;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Figcaption;
use Hirtz\Skeleton\Html\Figure;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Artwork extends Widget
{
    use TagAttributesTrait;
    use UrlTrait;

    protected Asset $asset;

    protected bool $adminLink = true;
    protected bool $aspectRatio = true;
    protected string|false $embedViewFile = 'widgets/_embed';
    protected int|false $lazyLoadingPosition = 5;
    protected bool|int $maxWidth = false;

    private ?Closure $caption;
    private ?Closure $figure;
    private ?Closure $link;
    private ?Closure $media;
    private ?Closure $wrapper;

    private static int $counter = 0;

    public function adminLink(bool $adminLink): self
    {
        $this->adminLink = $adminLink;
        return $this;
    }

    public function aspectRatio(bool $aspectRatio): static
    {
        $this->aspectRatio = $aspectRatio;
        return $this;
    }

    /**
     * @param Closure(?Figcaption): (string|Stringable|false|null)|null $caption
     */
    public function caption(?Closure $caption): static
    {
        $this->caption = $caption;
        return $this;
    }

    public function embedViewFile(string|false $embedViewFile): static
    {
        $this->embedViewFile = $embedViewFile;
        return $this;
    }

    /**
     * @param Closure(?Figure): (string|Stringable|null)|null $figure
     */
    public function figure(?Closure $figure): static
    {
        $this->figure = $figure;
        return $this;
    }

    public function lazyLoadingPosition(int $position): static
    {
        $this->lazyLoadingPosition = $position;
        return $this;
    }

    /**
     * @param Closure(?A): (string|Stringable|false|null)|null $link
     */
    public function link(?Closure $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function maxWidth(bool|int $maxWidth): static
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }

    /**
     * @param Closure(Media): (string|Stringable)|null $media
     */
    public function media(?Closure $media): static
    {
        $this->media = $media;
        return $this;
    }

    public function resetCounter(): static
    {
        self::$counter = 0;
        return $this;
    }

    /**
     * @param Closure(Div): (string|Stringable)|null $wrapper
     */
    public function wrapper(?Closure $wrapper): static
    {
        $this->wrapper = $wrapper;
        return $this;
    }

    #[Override]
    public function configure(): void
    {
        $this->url ??= $this->asset->getVisibleAttribute('link');

        if ($this->asset->file->hasDimensions() && $this->maxWidth !== false) {
            $width = $this->asset->file->width;

            if ($this->maxWidth === true || $width < $this->maxWidth) {
                $this->attributes['style']['max-width'] = "{$width}px";
            }
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $wrapper = Div::make()
            ->attributes($this->attributes)
            ->content($this->renderFigure());

        $admin = $this->adminLink ? AdminLink::tag($this->asset) : null;

        if ($admin) {
            $wrapper->addContent($admin)
                ->addClass('relative');
        }

        return $this->wrapper ? ($this->wrapper)($wrapper) : $wrapper;
    }

    protected function renderFigure(): string|Stringable
    {
        $caption = $this->renderCaption();
        $embed = $this->renderEmbed();
        $media = $this->renderMedia();

        if (!$caption && !$embed && !$this->figure) {
            return $media;
        }

        if ($embed) {
            $media = Div::make()
                ->class('relative')
                ->content($embed, $media);
        }

        $figure = Figure::make()
            ->content($media, $caption);

        return $this->figure ? ($this->figure)($figure) : $figure;
    }

    protected function renderCaption(): ?Stringable
    {
        $content = $this->asset->getVisibleAttribute('content') ?: null;

        if ($content) {
            if ($this->asset->contentType !== 'html') {
                $content = Html::encode($content);
            }

            $content = Figcaption::make()->content($content);
        }

        return $this->caption ? ($this->caption)($content) : $content;
    }

    protected function renderMedia(): ?Stringable
    {
        $media = Media::make()
            ->asset($this->asset)
            ->aspectRatio($this->aspectRatio);

        if ($this->lazyLoadingPosition !== false) {
            $media->lazyLoading($this->lazyLoadingPosition <= self::$counter);
            self::$counter++;
        }

        if ($this->media) {
            $media = ($this->media)($media);
        }

        $link = $this->url
            ? A::make()
                ->attribute('aria-label', $this->asset->getVisibleAttribute('name'))
                ->content($media)
                ->href($this->url)
            : null;

        $link = $this->link ? ($this->link)($link) : $link;

        return $link ?: $media;
    }

    protected function renderEmbed(): ?string
    {
        return $this->embedViewFile && $this->asset->getVisibleAttribute('embed_url')
            ? $this->view->render($this->embedViewFile, ['asset' => $this->asset], $this)
            : null;
    }
}
