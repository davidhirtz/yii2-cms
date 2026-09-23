<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Transformations\Transformation;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class MetaTags extends Widget
{
    use ContainerConfigurationTrait;
    use ModuleTrait;

    protected Category|Entry $model;

    /**
     * @var list<string>|null
     */
    protected ?array $languages = null;
    protected bool $enableHrefLangLinks = true;
    protected bool $enableCanonicalUrl = false;
    protected bool $enableImages = true;
    protected bool $enableSocialMetaTags = true;
    protected ?int $assetType = Asset::TYPE_META_IMAGE;
    protected Transformation|string|null $transformation = Transformation::NAME_OPEN_GRAPH;
    protected string|false $ogType = 'website';

    private UrlManager $urlManager;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->urlManager = Yii::$app->getUrlManager();
        parent::__construct($config);
    }

    public function model(Category|Entry $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param list<string>|null $languages the languages of the hreflang links, by default those of the URL manager
     */
    public function languages(?array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    public function enableHrefLangLinks(bool $enableHrefLangLinks = true): static
    {
        $this->enableHrefLangLinks = $enableHrefLangLinks;
        return $this;
    }

    public function enableCanonicalUrl(bool $enableCanonicalUrl = true): static
    {
        $this->enableCanonicalUrl = $enableCanonicalUrl;
        return $this;
    }

    public function enableImages(bool $enableImages = true): static
    {
        $this->enableImages = $enableImages;
        return $this;
    }

    public function enableSocialMetaTags(bool $enableSocialMetaTags = true): static
    {
        $this->enableSocialMetaTags = $enableSocialMetaTags;
        return $this;
    }

    /**
     * @param int|null $assetType the asset type offered as the share image, `null` for every asset
     */
    public function assetType(?int $assetType): static
    {
        $this->assetType = $assetType;
        return $this;
    }

    /**
     * The share image's transformation, or its name on the media module; `null` shares the original file, as does a
     * transformation the file is too small for.
     */
    public function transformation(Transformation|string|null $transformation): static
    {
        $this->transformation = $transformation;
        return $this;
    }

    public function ogType(string|false $ogType): static
    {
        $this->ogType = $ogType;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->enableImages = $this->enableImages
            && $this->model instanceof Entry
            && static::getModule()->enableEntryAssets;

        $this->languages ??= $this->urlManager->i18nUrl
            ? array_keys($this->urlManager->languages)
            : [];

        if (count($this->languages) < 2) {
            $this->enableHrefLangLinks = false;
        }

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $this->registerMetaTags();
        return '';
    }

    protected function registerMetaTags(): void
    {
        $this->setDocumentTitle();
        $this->setMetaDescription();

        if ($this->enableHrefLangLinks) {
            $this->registerHrefLangLinkTags();
        }

        if ($this->enableCanonicalUrl) {
            $this->registerCanonicalUrlTags();
        }

        if ($this->enableImages) {
            $this->registerImageMetaTags();
        }

        if ($this->enableSocialMetaTags) {
            $this->registerSocialMetaTags();
        }
    }

    protected function setDocumentTitle(): void
    {
        $title = $this->model->getVisibleAttribute('title') ?? $this->model->getI18nAttribute('name');
        $this->view->title($title);
    }

    protected function setMetaDescription(): void
    {
        $content = $this->model->getVisibleAttribute('description') ?? $this->model->getVisibleAttribute('content');

        if ($content) {
            $this->view->description($content);
        }
    }

    protected function registerHrefLangLinkTags(): void
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language): void {
                $route = $this->model->getRoute();

                if ($route) {
                    $this->view->registerHrefLangLinkTag($language, $this->urlManager->createAbsoluteUrl($route));
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    protected function registerDefaultHrefLangLinkTag(): void
    {
        $this->view->registerDefaultHrefLangLinkTag($this->urlManager->defaultLanguage);
    }

    protected function registerCanonicalUrlTags(): void
    {
        $route = $this->model->getRoute();

        if ($route) {
            $this->view->registerCanonicalTag($this->urlManager->createAbsoluteUrl($route));
        }
    }

    protected function registerSocialMetaTags(): void
    {
        if ($this->ogType) {
            $this->view->registerOpenGraphMetaTags($this->ogType);
        }
    }

    /**
     * A category carries no assets. The URL comes from the media module, which only serves a transformation it
     * declares under that name.
     */
    protected function registerImageMetaTags(): void
    {
        if (!$this->model instanceof AssetModelInterface) {
            return;
        }

        $transformation = is_string($this->transformation)
            ? File::getModule()->getTransformation($this->transformation)
            : $this->transformation;

        foreach ($this->model->assets as $asset) {
            if ($this->assetType && $this->assetType !== $asset->type) {
                continue;
            }

            $file = $asset->file;
            $url = $transformation ? $file->getTransformationUrl($transformation->name) : null;

            if ($url) {
                $this->view->registerImageMetaTags($url, ...$transformation->getSizeFor($file));
                continue;
            }

            $this->view->registerImageMetaTags($file->getUrl(), $file->width, $file->height);
        }
    }
}
