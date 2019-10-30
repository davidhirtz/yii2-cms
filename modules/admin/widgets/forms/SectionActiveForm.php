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

        if ($route = $this->model->entry->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();

            foreach ($this->model->getI18nAttributeNames('slug') as $language => $attributeName) {
                $route['language'] = $language;
                $baseUrl = $this->model->entry->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);
                $html .= $this->field($this->model, $attributeName)->slug(['baseUrl' => $baseUrl]);
            }
        }

        return $html;
    }
}