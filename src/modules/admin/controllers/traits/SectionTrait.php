<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait SectionTrait
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