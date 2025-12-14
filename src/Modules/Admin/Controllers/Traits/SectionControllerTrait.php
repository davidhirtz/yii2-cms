<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Section;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait SectionControllerTrait
{
    protected function findSection(int $id, ?string $permissionName = null): Section
    {
        if (!$section = Section::findOne($id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['section' => $section])) {
            throw new ForbiddenHttpException();
        }

        return $section;
    }
}
