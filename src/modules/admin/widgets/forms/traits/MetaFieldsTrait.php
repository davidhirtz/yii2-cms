<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TextareaField;
use Stringable;

trait MetaFieldsTrait
{
    protected function getTitleField(): ?Stringable
    {
        return InputField::make()
            ->property('title');
    }

    public function getDescriptionField(): ?Stringable
    {
        return TextareaField::make()
            ->property('description')
            ->addStyle(['min-height' => '4.5rem'], false);
    }
}