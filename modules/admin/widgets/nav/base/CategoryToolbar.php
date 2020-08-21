<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\LinkButtonTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;
use Yii;

/**
 * Class CategoryToolbar
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\nav\CategoryToolbar
 *
 * @property Category $model
 */
class CategoryToolbar extends Toolbar
{
    use LinkButtonTrait;
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->actions === null) {
            $this->actions = $this->hasForm() ? [$this->getFormSubmitButton()] : [$this->getCreateCategoryButton()];
        }

        if ($this->links === null) {
            $this->links = $this->hasForm() ? [$this->getLinkButton()] : [];
        }

        parent::init();
    }

    /**
     * @return string
     */
    protected function getCreateCategoryButton()
    {
        if (Yii::$app->getUser()->can('author')) {
            $type = Yii::$app->getRequest()->get('type', static::getModule()->defaultCategoryType);
            return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), ['create', 'type' => $type], [
                'class' => 'btn btn-primary',
            ]);
        }

        return '';
    }
}