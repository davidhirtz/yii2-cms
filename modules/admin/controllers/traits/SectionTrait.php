<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Section;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Trait SectionTrait
 * @package davidhirtz\yii2\cms\modules\admin\controllers\traits
 */
trait SectionTrait
{
    /**
     * @param int $id
     * @param string|null $permissionName
     * @return Section
     */
    protected function findSection(int $id, $permissionName = null)
    {
        if (!$section = Section::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['section' => $section])) {
            throw new ForbiddenHttpException();
        }

        return $section;
    }
}