<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\helpers\Html;
use davidhirtz\yii2\media\widgets\Picture;
use davidhirtz\yii2\skeleton\widgets\Widget;
use yii\helpers\ArrayHelper;

class Canvas extends Widget
{
    public ?Asset $asset = null;

    public array $captionOptions = [];
    public array $linkOptions = [];
    public array $pictureOptions = [];
    public array $wrapperOptions = ['class' => 'canvas'];

    public string $template = '{media}{embed}{caption}';
    public array $parts = [];

    public bool $enableLinkWrapper = true;

    public bool $enableMaxWidth = false;
    public ?int $defaultMaxWidth = null;

    public bool $enableWrapperHeight = true;
    public bool $setWrapperHeightWithAspectRatio = true;

    public int|false $lazyLoadingParentPosition = 2;
    public string $embedViewFile = '/widgets/_embed';

    public function init(): void
    {
        if ($this->asset) {
            $this->linkOptions['aria-label'] ??= $this->asset->getVisibleAttribute('name');

            if ($this->asset->file->hasDimensions()) {
                if ($this->enableWrapperHeight) {
                    $this->setWrapperHeight();
                }

                if ($this->enableMaxWidth) {
                    $width = $this->asset->file->width;

                    if ($this->defaultMaxWidth === null || $width < $this->defaultMaxWidth) {
                        $this->wrapperOptions['style']['max-width'] = "{$width}px";
                    }
                }
            }

            if ($this->lazyLoadingParentPosition !== false
                && $this->asset->parent->position > $this->lazyLoadingParentPosition) {
                $this->pictureOptions['imgOptions']['loading'] ??= 'lazy';
            }
        }

        if ($this->enableLinkWrapper) {
            $this->enableLinkWrapper = !str_contains($this->template, '{link}');
        }

        $this->wrapperOptions = array_filter($this->wrapperOptions);

        parent::init();
    }

    public function run(): string
    {
        $content = $this->getContent();
        return $this->wrapContent($content);
    }

    /**
     * @uses static::renderAdmin()
     * @uses static::renderCaption()
     * @uses static::renderEmbed()
     * @uses static::renderLink()
     * @uses static::renderMedia()
     */
    protected function getContent(): string
    {
        return preg_replace_callback(
            '/{(\\w+)}/',
            function ($matches) {
                $methodName = 'render' . ucfirst($matches[1]);
                return method_exists($this, $methodName) ? $this->$methodName() : $matches[0];
            },
            $this->template
        );
    }

    protected function renderAdmin(): string
    {
        return AdminLink::tag($this->asset);
    }

    protected function renderCaption(): string
    {
        if (!$content = $this->asset?->getVisibleAttribute('content')) {
            return '';
        }

        $encode = ArrayHelper::remove($this->captionOptions, 'encode', false);
        $content = $encode ? Html::encode($content) : $content;

        return Html::tag('div', $content, $this->captionOptions);
    }

    protected function renderEmbed(): string
    {
        if (!$this->asset?->getVisibleAttribute('embed_url')) {
            return '';
        }

        return $this->getView()->render($this->embedViewFile, ['asset' => $this->asset], $this);
    }

    protected function renderLink(): string
    {
        $link = $this->asset?->getVisibleAttribute('link');
        return $link ? Html::a('', $link, $this->linkOptions) : '';
    }

    protected function renderMedia(): string
    {
        if (!$this->asset) {
            return '';
        }

        return Picture::widget([
            'asset' => $this->asset,
            ...$this->pictureOptions
        ]);
    }

    protected function wrapContent(string $content): string
    {
        if ($this->enableLinkWrapper && ($link = $this->asset->getVisibleAttribute('link'))) {
            $options = ArrayHelper::merge($this->wrapperOptions, $this->linkOptions);
            return Html::a($content, $link, $options);
        }

        return $this->wrapperOptions ? Html::tag('div', $content, $this->wrapperOptions) : $content;
    }

    protected function setWrapperHeight(): void
    {
        if ($this->setWrapperHeightWithAspectRatio) {
            $this->wrapperOptions['style']['aspect-ratio'] ??= $this->getAspectRatio();
        } elseif ($percentage = $this->asset->file->getHeightPercentage()) {
            $this->wrapperOptions['style']['padding-top'] ??= "$percentage%";
        }
    }

    protected function getAspectRatio(): string
    {
        $width = $this->asset->file->width;
        $height = $this->asset->file->height;

        $gcd = $this->getGreatestCommonDivisor($width, $height);

        $smallestWidth = $width / $gcd;
        $smallestHeight = $height / $gcd;

        return "$smallestWidth/$smallestHeight";
    }

    protected function getGreatestCommonDivisor(int $width, int $height)
    {
        return $height !== 0
            ? $this->getGreatestCommonDivisor($height, $width % $height)
            : $width;
    }
}
