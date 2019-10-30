<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class SectionActiveForm.
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
                ['status', 'dropDownList', ArrayHelper::getColumn(Section::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(Section::getTypes(), 'name')],
                ['name'],
                ['content'],
                ['-'],
                ['slug'],
            ];
        }

        parent::init();
    }

    /**
     * @return string|false
     */
    public function slugField()
    {
        $html = '';

        if ($url = $this->getBaseUrl()) {
            $html .= $this->field($this->model, 'slug')->slug(['baseUrl' => $url]);

            if ($this->model->isI18nAttribute('slug')) {
                foreach (Yii::$app->getI18n()->languages as $language) {
                    if ($language !== Yii::$app->sourceLanguage) {
                        $html .= $this->field($this->model, $this->model->getI18nAttributeName('slug', $language))->slug(['baseUrl' => $this->getBaseUrl($language)]);
                    }
                }
            }
        }

        return $html;
    }

    /**
     * @param null $language
     * @return bool|string
     */
    private function getBaseUrl($language = null)
    {
        if ($route = $this->model->entry->getRoute()) {
            if ($language) {
                $route['language'] = $language;
            }

            $urlManager = Yii::$app->getUrlManager();
            return $this->model->entry->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);
        }

        return false;
    }
}