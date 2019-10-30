<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;

/**
 * Class ActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Asset|Category|Entry|Section $model
 */
class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm
{
    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function contentField()
    {
        $html = '';

        if ($this->model->contentType) {
            foreach ($this->model->getI18nAttributesNames('content') as $attributeName) {
                $field = $this->field($this->model, $attributeName);
                $html .= $this->model->contentType === 'html' ? $field->widget(CKEditor::class, $this->getContentConfig()) : $field->textarea();
            }
        }

        return $html;
    }

    /**
     * @return array
     */
    public function getContentConfig()
    {
        return ['validator' => $this->model->htmlValidator];
    }
}