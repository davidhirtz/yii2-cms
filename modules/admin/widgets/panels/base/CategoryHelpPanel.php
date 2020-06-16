<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class CategoryHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\CategoryHelpPanel
 *
 * @property Category $model
 */
class CategoryHelpPanel extends HelpPanel
{
    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getLinkButton(),
            $this->getCreateCategoryButton(),
        ]);
    }

    /**
     * @return string
     */
    protected function getCreateCategoryButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), ['category/create', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
        ]);
    }
}