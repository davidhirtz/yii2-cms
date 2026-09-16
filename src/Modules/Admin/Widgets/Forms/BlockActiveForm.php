<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Override;

/**
 * @property Block $model
 */
class BlockActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use CustomAttributeFieldsTrait;

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            $this->getStatusField(),
            $this->getTypeField(),
            $this->getNameField(),
            ...$this->getCustomAttributeFields(),
        ];
    }
}
