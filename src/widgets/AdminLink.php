<?php

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\helpers\Html;

class AdminLink extends Widget
{
    public Asset|Entry|Section|null $model = null;

    public array $linkOptions = [
        'class' => 'admin overlay',
        'target' => '_blank',
    ];

    public function run(): string
    {
        return $this->canUpdateModel() && ($route = $this->model->getAdminRoute())
            ? Html::a('', $route, $this->linkOptions)
            : '';
    }

    protected function canUpdateModel(): bool
    {
        if ($this->model instanceof Entry) {
            return Yii::$app->getUser()->can('entryUpdate', ['entry' => $this->model]);
        }

        if ($this->model instanceof Section) {
            return Yii::$app->getUser()->can('sectionUpdate', ['section' => $this->model]);
        }

        if ($this->model instanceof Asset) {
            return Yii::$app->getUser()->can('assetUpdate', ['asset' => $this->model]);
        }

        return false;
    }

    public static function tag(Asset|Entry|Section $model): string
    {
        return static::widget(['model' => $model]);
    }
}