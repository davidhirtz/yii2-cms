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
     * @return string
     */
    public function slugField(): string
    {
        return $this->showSlugField() ? parent::slugField() : '';
    }

    /**
     * @param string $language
     * @return string
     */
    protected function getSlugBaseUrl($language = null): string
    {
        $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
        $urlManager = Yii::$app->getUrlManager();
        $route = array_merge($this->model->entry->getRoute(), ['language' => $language, '#' => '']);

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