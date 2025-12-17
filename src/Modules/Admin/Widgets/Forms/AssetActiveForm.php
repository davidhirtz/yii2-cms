<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Modules\Admin\Widgets\Forms\Traits\AssetFieldsTrait;
use Hirtz\Skeleton\Widgets\Forms\Traits\TypeFieldTrait;

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
    }
}
