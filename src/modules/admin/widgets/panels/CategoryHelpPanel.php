<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * @property Category $model
 */
class CategoryHelpPanel extends HelpPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getCreateCategoryButton(),
            $this->getEntryGridViewButton(),
            $this->getLinkButton(),
        ]);
    }

    protected function getEntryGridViewButton(): string
    {
        if (!$this->model->hasEntriesEnabled()) {
            return '';
        }

        return Html::a(Html::iconText('book', Yii::t('cms', 'View All Entries')), ['entry/index', 'category' => $this->model->id], [
            'class' => 'btn btn-primary',
        ]);
    }

    protected function getCreateCategoryButton(): string
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), ['category/create', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
        ]);
    }
}
