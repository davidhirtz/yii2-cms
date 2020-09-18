<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Asset|Category|Entry|Section $model
 */
class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm
{
    use ModelTimestampTrait;

    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function statusField($options = [])
    {
        return ($statuses = $this->getStatuses()) ? $this->field($this->model, 'status', $options)->dropDownList($statuses) : '';
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function typeField($options = [])
    {
        return ($types = $this->getTypes()) ? $this->field($this->model, 'type', $options)->dropDownList($types) : '';
    }

    /**
     * @param array $options
     * @return string
     */
    public function descriptionField($options = []): string
    {
        $attribute = $this->model->getI18nAttributeName('description', ArrayHelper::remove($options, 'language'));
        return $this->field($this->model, $attribute, $options)->textarea();
    }

    /**
     * @param array $options
     * @return string
     */
    public function contentField($options = [])
    {
        if ($this->model->contentType) {
            $attribute = $this->model->getI18nAttributeName('content', ArrayHelper::remove($options, 'language'));
            $field = $this->field($this->model, $attribute, $options);

            return $this->model->contentType === 'html' ? $field->widget(CKEditor::class, $this->getContentConfig()) : $field->textarea();
        }

        return '';
    }

    /**
     * @param array $options
     * @return string
     */
    public function slugField($options = []): string
    {
        $language = ArrayHelper::remove($options, 'language');
        $attribute = $this->model->getI18nAttributeName('slug', $language);
        $options = array_merge(['enableClientValidation' => false], $options);

        return  $this->field($this->model, $attribute, $options)->slug([
            'baseUrl' => Html::tag('span', $this->getSlugBaseUrl($language), ['id' => $this->getSlugId($language)]),
        ]);
    }

    /**
     * Renders user information footer.
     */
    public function renderFooter()
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    /**
     * @return array
     */
    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
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
        return [
            'validator' => $this->model->htmlValidator,
            'clientOptions' => $this->model->htmlValidator !== false ? [] : [
                'allowedContent' => true,
            ],
        ];
    }

    /**
     * @param string|null $language
     * @return string
     */
    protected function getSlugBaseUrl($language = null): string
    {
        $manager = Yii::$app->getUrlManager();
        return rtrim($manager->createAbsoluteUrl(['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null]), '/') . '/';
    }

    /**
     * @param string|null $language
     * @return string
     */
    protected function getSlugId($language = null): string
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }
}