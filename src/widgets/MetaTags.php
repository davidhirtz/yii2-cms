<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\base\BaseObject;

class MetaTags extends BaseObject
{
    use ContainerConfigurationTrait;
    use ModuleTrait;

    public Category|Entry|null $model = null;

    public ?array $languages = null;

    /**
     * @var bool whether href links should be registered, defaults to `true`.
     */
    public bool $enableHrefLangLinks = true;

    /**
     * @var bool whether the canonical url should be registered, defaults to `false`.
     */
    public bool $enableCanonicalUrl = false;

    /**
     * @var bool whether assets should be registered as meta images
     */
    public bool $enableImages = true;

    /**
     * @var bool whether social meta-tags should be registered as meta images
     */
    public bool $enableSocialMetaTags = true;

    /**
     * @var int|null the asset type for meta images, if empty all assets of the entry will be included
     */
    public ?int $assetType = Asset::TYPE_META_IMAGE;

    /**
     * @var string|null the transformation for the meta-images, if null, the original asset file will be included
     */
    public ?string $transformationName = null;

    /**
     * @var string|false the og:type, if false, no og:type will be registered
     */
    public string|false $ogType = 'website';
    
    protected View $view;

    public function init(): void
    {
        $this->view ??= Yii::$app->getView();
        
        if ($this->enableImages) {
            $this->enableImages = $this->model instanceof Entry && static::getModule()->enableEntryAssets;
        }

        if ($this->languages === null) {
            $manager = Yii::$app->getUrlManager();

            if ($manager->i18nUrl || $manager->i18nSubdomain) {
                $this->languages = array_keys($manager->languages);
            }
        }

        if (!$this->languages) {
            $this->enableHrefLangLinks = false;
        }

        $this->registerMetaTags();

        parent::init();
    }

    public function registerMetaTags(): void
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

    public function registerHrefLangLinkTags(): void
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language) {
                if ($route = $this->model->getRoute()) {
                    $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
                    $this->view->registerHrefLangLinkTag($language, $url);
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    public function registerDefaultHrefLangLinkTag(): void
    {
        $this->view->registerDefaultHrefLangLinkTag(Yii::$app->getUrlManager()->defaultLanguage);
    }

    public function registerCanonicalUrlTags(): void
    {
        if ($route = $this->model->getRoute()) {
            $this->view->registerCanonicalTag(Yii::$app->getUrlManager()->createAbsoluteUrl($route));
        }
    }

    public function registerSocialMetaTags(): void
    {
        if ($this->ogType) {
            $this->view->registerOpenGraphMetaTags($this->ogType);
        }
    }

    public function registerImageMetaTags(): void
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
