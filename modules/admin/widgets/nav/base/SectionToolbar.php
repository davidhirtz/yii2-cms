<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\LinkButtonTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;
use Yii;

/**
 * Class SectionToolbar
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\nav\SectionToolbar
 *
 * @property Entry|Section $model
 */
class SectionToolbar extends Toolbar
{
    use LinkButtonTrait;
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->actions === null) {
            $this->actions = $this->hasForm() ? [$this->getFormSubmitButton()] : [$this->getCreateSectionButton()];
        }

        if ($this->links === null) {
            $this->links = $this->hasForm() ? [$this->getLinkButton()] : [];
        }

        parent::init();
    }

    /**
     * @return string
     */
    protected function getCreateSectionButton()
    {
        if (Yii::$app->getUser()->can('author')) {
            return Html::a(Html::iconText('plus', Yii::t('cms', 'New Section')), ['create', 'entry' => $this->model->id], [
                'class' => 'btn btn-primary btn-submit',
            ]);
        }

        return '';
    }

    /**
     * @return bool
     */
    public function hasForm(): bool
    {
        return $this->model instanceof Section;
    }
}