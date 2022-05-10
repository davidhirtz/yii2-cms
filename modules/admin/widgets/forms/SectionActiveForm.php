<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Section;
use Yii;

/**
 * Class SectionActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public $hasStickyButtons = true;

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

        return $this->model->entry->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);
    }

    /**
     * @return bool
     */
    protected function showSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}