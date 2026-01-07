<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class MetaTags extends Widget
{
    use ContainerConfigurationTrait;
    use ModuleTrait;

    protected Category|Entry $model;

    protected ?array $languages = null;
    protected bool $enableHrefLangLinks = true;
    protected bool $enableCanonicalUrl = false;
    protected bool $enableImages = true;
    protected bool $enableSocialMetaTags = true;
    protected ?int $assetType = Asset::TYPE_META_IMAGE;
    protected ?string $transformationName = null;
    protected string|false $ogType = 'website';

    public function model(Category|Entry $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->enableImages = $this->enableImages
            && $this->model instanceof Entry
            && static::getModule()->enableEntryAssets;

        if (null === $this->languages) {
            $manager = Yii::$app->getUrlManager();

            $this->languages = $manager->i18nUrl || $manager->i18nSubdomain ? array_keys($manager->languages) : [];
        }

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
        $title = $this->model->getI18nAttribute('title') ?? $this->model->getI18nAttribute('name');
        $this->view->title($title);
    }

    protected function setMetaDescription(): void
    {
        $content = $this->model->getI18nAttribute('description') ?? $this->model->getI18nAttribute('content');

        if ($content) {
            $this->view->description($content);
        }
    }

    protected function registerHrefLangLinkTags(): void
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language): void {
                if ($route = $this->model->getRoute()) {
                    $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
                    $this->view->registerHrefLangLinkTag($language, $url);
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    protected function registerDefaultHrefLangLinkTag(): void
    {
        $this->view->registerDefaultHrefLangLinkTag(Yii::$app->getUrlManager()->defaultLanguage);
    }

    protected function registerCanonicalUrlTags(): void
    {
        if ($route = $this->model->getRoute()) {
            $this->view->registerCanonicalTag(Yii::$app->getUrlManager()->createAbsoluteUrl($route));
        }
    }

    protected function registerSocialMetaTags(): void
    {
        if ($this->ogType) {
            $this->view->registerOpenGraphMetaTags($this->ogType);
        }
    }

    protected function registerImageMetaTags(): void
    {
        foreach ($this->model->assets as $asset) {
            if ($asset->isSectionAsset() || ($this->assetType && $this->assetType !== $asset->type)) {
                continue;
            }

            $file = $asset->file;

            if ($this->transformationName) {
                $url = $file->getTransformationUrl($this->transformationName);

                if ($url) {
                    $transformation = $file->getTransformations()[$this->transformationName] ?? [];

                    if (array_key_exists('width', $transformation)) {
                        $height = $file->getTransformations()[$this->transformationName]['height']
                            ?? round($file->height * ($transformation['width'] / $file->width));

                        $this->view->registerImageMetaTags($url, (int)$transformation['width'], (int)$height);
                        continue;
                    }
                }
            }

            $this->view->registerImageMetaTags($file->getUrl(), $file->width, $file->height);
        }
    }
}
