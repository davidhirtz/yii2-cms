<?php
namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\helpers\Picture;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\Widget;
use yii\helpers\ArrayHelper;

class Canvas extends Widget
{
    public ?Asset $asset = null;

    public array $captionOptions = [];
    public array $linkOptions = [];
    public array $pictureOptions = [];
    public array $wrapperOptions = [];

    public bool $enableCaption = true;
    public bool $enableEmbedUrl = true;
    public bool $enableLinkWrapper = true;
    public bool $enableWrapperHeight = true;

    public int|false $lazyLoadingParentPosition = 2;
    public string $embedViewFile = '_embed';

    public function init(): void
    {
        if ($this->enableWrapperHeight && $this->asset->file->hasDimensions()) {
            $this->setWrapperHeight();
        }

        if ($this->lazyLoadingParentPosition !== false
            && $this->asset->parent->position > $this->lazyLoadingParentPosition) {
            $this->pictureOptions['imgOptions']['loading'] ??= 'lazy';
        }

        $this->linkOptions['aria-label'] ??= $this->asset->getI18nAttribute('name');

        $this->wrapperOptions = array_filter($this->wrapperOptions);

        parent::init();
    }

    public function run(): string
    {
        $content = $this->renderContent();
        return $this->renderWrapper($content);
    }

    protected function renderContent(): string
    {
        $content = $this->renderMediaTag();

        if ($this->enableEmbedUrl) {
            $content .= $this->renderEmbed();
        }

        if ($this->enableCaption) {
            $content .= $this->renderCaption();
        }

        return $content;
    }

    protected function renderMediaTag(): string
    {
        return Picture::tag($this->asset, $this->pictureOptions);
    }

    protected function renderCaption(): string
    {
        if (!$content = $this->asset->getI18nAttribute('content')) {
            return '';
        }

        $encode = ArrayHelper::remove($this->captionOptions, 'encode', false);
        $content = $encode ? Html::encode($content) : $content;

        return Html::tag('div', $content, $this->captionOptions);
    }

    protected function renderEmbed(): string
    {
        if (!$this->asset->getI18nAttribute('embed_url')) {
            return '';
        }

        return $this->getView()->render($this->embedViewFile, [
            'asset' => $this->asset,
        ]);
    }

    protected function renderWrapper(string $content): string
    {
        if ($this->enableLinkWrapper && ($link = $this->asset->getI18nAttribute('link'))) {
            $options = ArrayHelper::merge($this->wrapperOptions, $this->linkOptions);
            return Html::a($content, $link, $options);
        }

        return $this->wrapperOptions ? Html::tag('div', $content, $this->wrapperOptions) : $content;
    }

    protected function setWrapperHeight(): void
    {
        $this->wrapperOptions['style']['padding-top'] ??= $this->asset->file->getHeightPercentage() . '%';
    }
}