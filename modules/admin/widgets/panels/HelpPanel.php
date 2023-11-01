<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\ActiveRecord;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\traits\LinkButtonTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

abstract class HelpPanel extends \davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel
{
    use LinkButtonTrait;

    public ?ActiveRecord $model = null;

    public function init(): void
    {
        if ($this->title === null) {
            $this->title = Yii::t('skeleton', 'Operations');
        }

        if ($this->content === null) {
            $this->content = $this->renderButtonToolbar($this->getButtons());
        }

        parent::init();
    }

    protected function getCloneButton() :string
    {
        return Html::a(Html::iconText('paste', Yii::t('cms', 'Duplicate')), ['clone', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    abstract protected function getButtons(): array;
}