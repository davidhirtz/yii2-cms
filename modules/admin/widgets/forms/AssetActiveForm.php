<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AssetActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public $hasStickyButtons = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'thumbnail',
                '-',
                'status',
                'type',
                'name',
                'content',
                'alt_text',
                'link',
            ];
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function thumbnailField()
    {
        $file = $this->model->file;
        return $file->hasPreview() ? $this->row($this->offset(Html::img($file->folder->getUploadUrl() . $file->getFilename(), ['class' => 'img-transparent']))) : '';
    }

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
    public function altTextField($options = [])
    {
        $language = ArrayHelper::remove($options, 'language');
        $attribute = $this->model->getI18nAttributeName('alt_text', $language);

        if (!isset($options['inputOptions']['placeholder'])) {
            $options['inputOptions']['placeholder'] = $this->model->file->getI18nAttribute('alt_text', $language);
        }

        return $this->field($this->model, $attribute, $options);
    }
}