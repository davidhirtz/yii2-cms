<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\traits;

use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
use Yii;

trait UpdateFileButtonTrait
{
    protected function getUpdateFileButton(): ?Stringable
    {
        return Yii::$app->getUser()->can(File::AUTH_FILE_CREATE)
            ? Button::make()
                ->secondary()
                ->icon('image')
                ->text(Yii::t('media', 'Edit File'))
                ->href(['/admin/file/update', 'id' => $this->model->file_id])
                ->target('_blank')
            : null;
    }
}
