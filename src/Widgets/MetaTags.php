<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
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
    protected ?string $transformationName = null;
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
     * A category carries no assets, and `File::getTransformations()` is the relation to the generated derivatives —
     * the preset the name refers to lives on the media module.
     */
    protected function registerImageMetaTags(): void
    {
        if (!$this->model instanceof AssetModelInterface) {
            return;
        }

        $transformation = $this->transformationName
            ? File::getModule()->getTransformations()[$this->transformationName] ?? null
            : null;

        foreach ($this->model->assets as $asset) {
            if ($this->assetType && $this->assetType !== $asset->type) {
                continue;
            }

            $file = $asset->file;
            $url = $transformation ? $file->getTransformationUrl((string)$this->transformationName) : null;

            if ($url) {
                $width = $transformation->getWidthFor($file);
                $height = $transformation->getHeight()
                    ?? (int)round($file->height * ($width / max($file->width, 1)));

                $this->view->registerImageMetaTags($url, $width, $height);
                continue;
            }

            $this->view->registerImageMetaTags($file->getUrl(), $file->width, $file->height);
        }
    }
}
