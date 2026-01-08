<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Helpers\AspectRatio;
use Hirtz\Media\Helpers\Html;
use Hirtz\Media\Widgets\Picture;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Canvas extends Widget
{
    use TagAttributesTrait;
    use TagUrlTrait;

    protected Asset $asset;

    protected array $captionAttributes = [];
    protected array $linkAttributes = [];
    protected array $pictureAttributes = [];

    protected string $layout = '{media}{embed}{caption}';
    protected array $parts = [];

    protected bool $enableLinkWrapper = true;

    protected bool $enableMaxWidth = false;
    protected ?int $defaultMaxWidth = null;

    protected bool $enableWrapperHeight = true;

    protected int|false $lazyLoadingParentPosition = 2;
    protected string $embedViewFile = 'widgets/_embed';

    #[Override]
    public function configure(): void
    {
        $this->attributes['class'] ??= 'canvas';
        $this->linkAttributes['aria-label'] ??= $this->asset->getVisibleAttribute('name');

        $this->url ??= $this->asset->getVisibleAttribute('link');

        if ($this->asset->file->hasDimensions()) {
            if ($this->enableWrapperHeight) {
                $this->attributes['style']['aspect-ratio'] ??= $this->getAspectRatio();
            }

            if ($this->enableMaxWidth) {
                $width = $this->asset->file->width;

                if ($this->defaultMaxWidth === null || $width < $this->defaultMaxWidth) {
                    $this->attributes['style']['max-width'] = "{$width}px";
                }
            }
        }

        if (
            false !== $this->lazyLoadingParentPosition
            && $this->asset->parent->position > $this->lazyLoadingParentPosition
        ) {
            $this->pictureAttributes['imgOptions']['loading'] ??= 'lazy';
        }

        if ($this->enableLinkWrapper) {
            $this->enableLinkWrapper = !str_contains($this->layout, '{link}');
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->wrapContent($this->getContent());
    }

    protected function getContent(): string
    {
        return strtr($this->layout, [
            '{admin}' => $this->getAdminLink(),
            '{caption}' => $this->getCaption(),
            '{embed}' => $this->getEmbed(),
            '{link}' => $this->getLink(),
            '{media}' => $this->getMedia(),
        ]);
    }

    protected function getAdminLink(): string
    {
        return AdminLink::tag($this->asset);
    }

    protected function getCaption(): ?Stringable
    {
        $content = $this->asset->getVisibleAttribute('content');

        if ('html' !== $this->asset->contentType) {
            $content = $content ? Html::encode($content) : '';
        }

        return $content
            ? Div::make()->attributes($this->captionAttributes)->content($content)
            : null;
    }

    protected function getEmbed(): string
    {
        if (!$this->asset->getVisibleAttribute('embed_url')) {
            return '';
        }

        return $this->view->render($this->embedViewFile, ['asset' => $this->asset], $this);
    }

    protected function getLink(): ?Stringable
    {
        return $this->url ? A::make()->attributes($this->linkAttributes)->href($this->url) : null;
    }

    protected function getMedia(): ?Stringable
    {
        $imgAttributes = ArrayHelper::remove($this->pictureAttributes, 'imgAttributes', []);
        $webpAttributes = ArrayHelper::remove($this->pictureAttributes, 'webpAttributes', []);

        return Picture::make()
            ->asset($this->asset)
            ->pictureAttributes($this->pictureAttributes)
            ->imgAttributes($imgAttributes)
            ->webpAttributes($webpAttributes);
    }

    protected function wrapContent(string $content): Stringable
    {
        return $this->enableLinkWrapper && $this->url
            ? A::make()
                ->attributes($this->linkAttributes)
                ->addAttributes($this->attributes)
                ->href($this->url)
                ->content($content)
            : Div::make()
                ->attributes($this->attributes)
                ->content($content);
    }

    protected function getAspectRatio(): ?string
    {
        return $this->asset->file->hasDimensions()
            ? (string)(new AspectRatio($this->asset->file))
            : null;
    }
}
