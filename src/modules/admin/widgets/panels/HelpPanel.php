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
        $this->title ??= Yii::t('skeleton', 'Operations');
        $this->content ??= $this->renderButtonToolbar($this->getButtons());

        parent::init();
    }

    protected function getDuplicateButton(array $options = []): string
    {
        return Html::a(Html::iconText('paste', Yii::t('cms', 'Duplicate')), ['duplicate', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
            ...$options,
        ]);
    }

    abstract protected function getButtons(): array;
}