<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\forms;

use Hirtz\Cms\models\Asset;
use Hirtz\Media\modules\admin\widgets\forms\traits\AssetFieldsTrait;
use Hirtz\Skeleton\widgets\forms\traits\TypeFieldTrait;

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
