<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Media\Modules\Admin\Widgets\Forms\Traits\AssetFieldsTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Override;
use Stringable;

/**
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use AssetFieldsTrait;
    use CustomAttributeFieldsTrait;

    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            [
                $this->getPreview(),
            ],
            [
                $this->getStatusField(),
                $this->getTypeField(),
                $this->getNameField(),
                $this->getContentField(),
                $this->getAltTextField(),
                $this->getLinkField(),
                $this->getEmbedUrlField(),
                ...$this->getCustomAttributeFields(),
            ],
        ];

        parent::configure();
    }

    protected function getEmbedUrlField(): ?Stringable
    {
        return InputField::make()
            ->property('embed_url');
    }
}
