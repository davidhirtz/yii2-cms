<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;

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
     * @param array $options
     * @return \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField|\yii\widgets\ActiveField|string
     */
    public function statusField($options = [])
    {
        return ($statuses = $this->getStatuses()) ? $this->field($this->model, 'status')->dropdownList($statuses, $options) : '';
    }

    /**
     * @param array $options
     * @return \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField|\yii\widgets\ActiveField|string
     */
    public function typeField($options = [])
    {
        return ($types = $this->getTypes()) ? $this->field($this->model, 'type')->dropdownList($this->getTypes(), $options) : '';
    }

    /**
     * @param array $options
     * @return string
     */
    public function descriptionField($options = []): string
    {
        $html = '';
        foreach ($this->model->getI18nAttributeNames('description') as $attributeName) {
            $html .= $this->field($this->model, $attributeName)->textarea($options);
        }

        return $html;
    }

    /**
     * @param array $options
     * @return string
     */
    public function contentField($options = [])
    {
        $html = '';
        if ($this->model->contentType) {
            foreach ($this->model->getI18nAttributeNames('content') as $attributeName) {
                $field = $this->field($this->model, $attributeName);
                $html .= $this->model->contentType === 'html' ? $field->widget(CKEditor::class, $this->getContentConfig()) : $field->textarea($options);
            }
        }

        return $html;
    }

    /**
     * @param array $options
     * @return string
     */
    public function slugField($options = []): string
    {
        $html = '';
        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attributeName) {
            $html .= $this->field($this->model, $attributeName)->slug(array_merge($options, ['baseUrl' => $this->getSlugBaseUrl($language)]));
        }

        return $html;
    }

    /**
     * @return array
     */
    protected function getStatuses(): array
    {
        return ArrayHelper::getColumn($this->model::getStatuses(), 'name');
    }

    /**
     * @return array
     */
    protected function getTypes(): array
    {
        return ArrayHelper::getColumn($this->model::getTypes(), 'name');
    }

    /**
     * @return array
     */
    protected function getContentConfig(): array
    {
        return ['validator' => $this->model->htmlValidator];
    }

    /**
     * @param string $language
     * @return string
     */
    protected function getSlugBaseUrl($language = null): string
    {
        return Yii::$app->getUrlManager()->createAbsoluteUrl(['/', 'language' => $language]);
    }
}