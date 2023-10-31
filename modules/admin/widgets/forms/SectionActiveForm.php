<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\helpers\Html;

/**
 * Renders an active form for the {@link Section} model.
 *
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public bool $hasStickyButtons = true;

    /**
     * @var int|false
     */
    public $maxBaseUrlLength = 70;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'status',
                'type',
                'name',
                'content',
                'slug',
            ];
        }

        parent::init();
    }

    /**
     * @param array $options
     * @return string
     */
    public function slugField($options = []): string
    {
        return $this->showSlugField() ? parent::slugField($options) : '';
    }

    /**
     * @param string|null $language
     * @return string
     */
    public function getSlugBaseUrl($language = null): string
    {
        $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
        $urlManager = Yii::$app->getUrlManager();
        $route = array_merge($this->model->entry->getRoute(), ['language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null, '#' => '']);

        $url = $this->model->entry->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);

        if ($this->maxBaseUrlLength && strlen($url) > $this->maxBaseUrlLength) {
            $url = Html::tag('span', substr($url, 0, $this->maxBaseUrlLength) . '…#', ['title' => $url]);
        }

        return $url;
    }

    /**
     * @return bool
     */
    protected function showSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}