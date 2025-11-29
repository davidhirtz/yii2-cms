<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\CategoryParentIdFieldTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\MetaFieldsTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\SlugFieldTrait;
use Override;

/**
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryParentIdFieldTrait;
    use MetaFieldsTrait;
    use SlugFieldTrait;

    #[Override]
    public function configure(): void
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getParentIdField(),
                $this->getTypeField(),
                $this->getNameField(),
                $this->getContentField(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
            ],
        ];

        parent::configure();
    }
}
