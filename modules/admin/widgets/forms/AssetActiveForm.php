<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
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
        $fields = [];
        foreach ($this->model->getI18nAttributeNames('alt_text') as $language => $attributeName) {
            $options['inputOptions']['placeholder'] = $this->model->file->getI18nAttribute('alt_text', $language);
            $fields[] = $this->field($this->model, $attributeName, $options);
        }

        return implode('', $fields);
    }
}