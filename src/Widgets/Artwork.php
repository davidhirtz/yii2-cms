<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Closure;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Media\Helpers\Html;
use Hirtz\Media\Widgets\Media;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Figcaption;
use Hirtz\Skeleton\Html\Figure;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\AdminLink;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Artwork extends Widget
{
    use TagAttributesTrait;
    use UrlTrait;

    protected Asset $asset;

    protected bool $adminLink = false;
    protected bool $aspectRatio = true;
    protected string|false $embedViewFile = 'widgets/_embed';
    protected int|false $lazyLoadingPosition = 5;
    protected bool|int $maxWidth = false;

    /**
     * @var list<Closure>|null
     */
    private ?array $captionClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $figureClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $linkClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $mediaClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $wrapperClosures = null;

    private static int $counter = 0;

    public function asset(Asset $asset): static
    {
        $this->asset = $asset;
        return $this;
    }

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
     * @param Closure(?Figcaption): ?Figcaption $caption
     */
    public function caption(Closure $caption): static
    {
        $this->captionClosures[] = $caption;
        return $this;
    }

    public function embedViewFile(string|false $embedViewFile): static
    {
        $this->embedViewFile = $embedViewFile;
        return $this;
    }

    /**
     * @param Closure(Figure): Figure $figure
     */
    public function figure(Closure $figure): static
    {
        $this->figureClosures[] = $figure;
        return $this;
    }

    public function lazyLoadingPosition(int|false $position): static
    {
        $this->lazyLoadingPosition = $position;
        return $this;
    }

    /**
     * @param Closure(?A): ?A $link
     */
    public function link(Closure $link): static
    {
        $this->linkClosures[] = $link;
        return $this;
    }

    public function maxWidth(bool|int $maxWidth): static
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }

    /**
     * @param Closure(Media): Media $media
     */
    public function media(Closure $media): static
    {
        $this->mediaClosures[] = $media;
        return $this;
    }

    public function resetCounter(): static
    {
        self::reset();
        return $this;
    }

    /**
     * The counter decides which artworks load eagerly, so it belongs to the request: the cms `Bootstrap` resets it.
     */
    public static function reset(): void
    {
        self::$counter = 0;
    }

    /**
     * @param Closure(Div): Div $wrapper
     */
    public function wrapper(Closure $wrapper): static
    {
        $this->wrapperClosures[] = $wrapper;
        return $this;
    }

    #[Override]
    public function configure(): void
    {
        $this->url ??= $this->asset->getVisibleAttribute('link') ?: null;

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

        return $this->evaluate($this->wrapperClosures, $wrapper);
    }

    protected function renderFigure(): string|Stringable
    {
        $caption = $this->renderCaption();
        $embed = $this->renderEmbed();
        $media = $this->renderMedia();

        if (!$caption && !$embed && !$this->figureClosures) {
            return $media;
        }

        if ($embed) {
            $media = Div::make()
                ->class('relative')
                ->content($embed, $media);
        }

        $figure = Figure::make()
            ->content($media, $caption);

        return $this->evaluate($this->figureClosures, $figure);
    }

    protected function renderCaption(): ?Figcaption
    {
        $content = $this->asset->getVisibleAttribute('content') ?: null;

        if ($content) {
            if (!$this->asset->getCustomAttribute('content') instanceof HtmlCustomAttribute) {
                $content = Html::encode($content);
            }

            $content = Figcaption::make()->content($content);
        }

        return $this->evaluate($this->captionClosures, $content);
    }

    protected function renderMedia(): ?Stringable
    {
        $media = $this->evaluate($this->mediaClosures, $this->makeMedia());

        $link = $this->url
            ? A::make()
                ->attribute('aria-label', $this->asset->getVisibleAttribute('name'))
                ->content($media)
                ->href($this->url)
            : null;

        return $this->evaluate($this->linkClosures, $link) ?? $media;
    }

    /**
     * Builds the media before the `media()` closures run, so a subclass setting its defaults here leaves the
     * caller's closures the last word — a closure it registered in `configure()` would run after them.
     */
    protected function makeMedia(): Media
    {
        $media = Media::make()
            ->asset($this->asset)
            ->aspectRatio($this->aspectRatio);

        if ($this->lazyLoadingPosition !== false) {
            $media->lazyLoading($this->lazyLoadingPosition <= self::$counter);
            self::$counter++;
        }

        return $media;
    }

    protected function renderEmbed(): ?string
    {
        return $this->embedViewFile && $this->asset->getVisibleAttribute('embed_url')
            ? $this->view->render($this->embedViewFile, ['asset' => $this->asset], $this)
            : null;
    }
}
