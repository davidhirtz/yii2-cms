<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\web\View;
use Yii;
use yii\base\BaseObject;

/**
 * Class MetaTags.
 * @package davidhirtz\yii2\cms\widgets
 */
class MetaTags extends BaseObject
{
    use ModuleTrait;

    /**
     * @var Category|Entry
     */
    public $model;

    /**
     * @var array
     */
    public $languages;

    /**
     * @var bool whether href links should be registered, defaults to `true`.
     */
    public $enableHrefLangLinks = true;

    /**
     * @var bool whether the canonical url should be registered, defaults to `false`.
     */
    public $enableCanonicalUrl = false;

    /**
     * @var bool whether assets should be registered as meta images
     */
    public $enableImages = true;

    /**
     * @var bool whether social meta tags should be registered as meta images
     */
    public $enableSocialMetaTags = true;

    /**
     * @var int the asset type for meta images, if empty all assets of the entry will be included
     */
    public $assetType;

    /**
     * @var string the transformation for the meta images, if empty the original asset file will
     * be included
     */
    public $transformationName;

    /**
     * @var string|bool
     */
    public $ogType = 'website';

    /**
     * @var string|bool
     */
    public $twitterCard = 'summary_large_image';

    /**
     * @var View
     */
    private $_view;

    /**
     * @param array $config
     */
    public static function register($config = [])
    {
        (new static($config))->run();
    }

    /**
     * @inheritDoc
     */
    public function init()
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

    /**
     * Registers all meta tags.
     */
    public function run()
    {
        $this->setTitle();
        $this->setDescription();

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

    /**
     * Sets title.
     */
    public function setTitle()
    {
        $this->getView()->setTitle($this->model->getI18nAttribute('title') ?: $this->model->getI18nAttribute('name'));
    }

    /**
     * Sets description.
     */
    public function setDescription()
    {
        $this->getView()->setDescription($this->model->getI18nAttribute('description') ?: $this->model->getI18nAttribute('content'));
    }

    /**
     * Registers href language tags.
     */
    public function registerHrefLangLinkTags()
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language) {
                if ($route = $this->model->getRoute()) {
                    $this->getView()->registerHrefLangLinkTag($language, Yii::$app->getUrlManager()->createAbsoluteUrl($route, true));
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    /**
     * Registers default language tag.
     */
    public function registerDefaultHrefLangLinkTag()
    {
        $this->getView()->registerDefaultHrefLangLinkTag(Yii::$app->getUrlManager()->defaultLanguage);
    }

    /**
     * Registers canonical url.
     */
    public function registerCanonicalUrlTags()
    {
        if ($route = $this->model->getRoute()) {
            $this->getView()->registerCanonicalTag(Yii::$app->getUrlManager()->createAbsoluteUrl($route));
        }
    }

    /**
     * Registers social meta tags.
     */
    public function registerSocialMetaTags()
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

    /**
     * Registers images.
     */
    public function registerImageMetaTags()
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

    /**
     * @return View
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->controller->getView();
        }

        return $this->_view;
    }

    /**
     * @param \yii\base\View $view
     */
    public function setView($view)
    {
        $this->_view = $view;
    }
}