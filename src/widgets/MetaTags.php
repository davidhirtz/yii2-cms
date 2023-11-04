<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\web\View;
use Yii;
use yii\base\BaseObject;

class MetaTags extends BaseObject
{
    use ModuleTrait;

    public Category|Entry|null $model;

    /**
     * @var array|null
     */
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
     * @var bool whether social meta tags should be registered as meta images
     */
    public bool $enableSocialMetaTags = true;

    /**
     * @var int|null the asset type for meta images, if empty all assets of the entry will be included
     */
    public ?int $assetType = null;

    /**
     * @var string|null the transformation for the meta-images, if null, the original asset file will be included
     */
    public ?string $transformationName = null;

    /**
     * @var string|bool
     */
    public string|false $ogType = 'website';

    /**
     * @var string|bool
     */
    public string|false $twitterCard = 'summary_large_image';

    private ?View $_view = null;

    public function init(): void
    {
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

        parent::init();
    }

    public function run(): void
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
        $this->getView()->setTitle($this->model->getI18nAttribute('title')
            ?: $this->model->getI18nAttribute('name'));
    }

    protected function setMetaDescription(): void
    {
        $this->getView()->setMetaDescription($this->model->getI18nAttribute('description')
            ?: $this->model->getI18nAttribute('content'));
    }

    public function registerHrefLangLinkTags(): void
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language) {
                if ($route = $this->model->getRoute()) {
                    $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route, true);
                    $this->getView()->registerHrefLangLinkTag($language, $url);
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    public function registerDefaultHrefLangLinkTag(): void
    {
        $this->getView()->registerDefaultHrefLangLinkTag(Yii::$app->getUrlManager()->defaultLanguage);
    }

    public function registerCanonicalUrlTags(): void
    {
        if ($route = $this->model->getRoute()) {
            $this->getView()->registerCanonicalTag(Yii::$app->getUrlManager()->createAbsoluteUrl($route));
        }
    }

    public function registerSocialMetaTags(): void
    {
        if ($this->ogType) {
            $this->getView()->registerOpenGraphMetaTags($this->ogType);
        }

        if ($this->twitterCard) {
            if ($this->twitterCard == 'summary_large_image' && (!$this->enableImages || !$this->model->asset_count)) {
                $this->twitterCard = 'summary';
            }

            $this->getView()->registerTwitterCardMetaTags($this->twitterCard);
        }
    }

    public function registerImageMetaTags(): void
    {
        foreach ($this->model->assets as $asset) {
            if (!$asset->section_id && (!$this->assetType || $this->assetType == $asset->type)) {
                $file = $asset->file;
                if ($this->transformationName) {
                    if ($url = $file->getTransformationUrl($this->transformationName)) {
                        $width = $file->getTransformationOptions($this->transformationName, 'width');
                        $height = $file->getTransformationOptions($this->transformationName, 'height') ?: ceil($width * $file->getHeightPercentage() / 100);
                        $this->getView()->registerImageMetaTags($url, $width, $height);
                    }
                } else {
                    $this->getView()->registerImageMetaTags($file->getUrl(), $file->width, $file->height);
                }
            }
        }
    }

    public function getView(): View
    {
        $this->_view ??= Yii::$app->controller->getView();
        return $this->_view;
    }

    /** @noinspection PhpUnused */
    public function setView(View $view): void
    {
        $this->_view = $view;
    }

    public static function register(array $config = []): void
    {
        Yii::createObject(static::class, $config)->run();
    }
}