<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\panels\traits;

use Hirtz\Media\models\File;
use Hirtz\Skeleton\html\Button;
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
