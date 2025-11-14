<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\modules\admin\widgets\forms\traits\AssetFieldsTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\TypeFieldTrait;

/**
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    use AssetFieldsTrait;
    use TypeFieldTrait;

    /**
     * @uses static::statusField()
     * @uses static::typeField()
     * @uses static::contentField()
     * @uses static::altTextField()
     */
    #[\Override]
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
