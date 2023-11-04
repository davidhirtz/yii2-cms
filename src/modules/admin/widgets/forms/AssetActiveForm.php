<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\modules\admin\widgets\forms\traits\AssetFieldsTrait;

/**
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    use AssetFieldsTrait;

    public function init(): void
    {
        $this->fields ??= [
            'status',
            'type',
            'name',
            'content',
            'alt_text',
            'link',
            'embed_url',
        ];

        parent::init();
    }
}