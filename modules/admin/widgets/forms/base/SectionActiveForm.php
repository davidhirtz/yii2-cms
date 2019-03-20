<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class SectionActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property SectionForm $model
 */
class SectionActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                ['status', 'dropDownList', ArrayHelper::getColumn(SectionForm::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(SectionForm::getTypes(), 'name')],
                ['name'],
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea'],
                ['-'],
                ['slug', ['enableClientValidation' => false], 'url'],
            ];
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return trim(Url::to($this->model->page->getRoute(), true), '/') . '#';
    }
}