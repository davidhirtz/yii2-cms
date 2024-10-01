<?php

namespace davidhirtz\yii2\cms\modules\admin\helpers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

class FrontendLink
{
    protected readonly Entry $entry;

    public function __construct(
        protected readonly Entry|Section $model,
        protected array $options = [],
    ) {
        $this->entry = $this->model instanceof Section ? $this->model->entry : $this->model;
    }

    public function __toString(): string
    {
        $route = $this->model->getRoute();

        if (!$route) {
            return '';
        }

        $manager = Yii::$app->getUrlManager();
        $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

        if ($this->isDisabled()) {
            Html::addCssClass($this->options, 'text-invalid');
        }

        return Html::a(Html::encode($url), $url, [
            'target' => '_blank',
            ...$this->options,
        ]);
    }

    protected function isDisabled(): bool
    {
        if ($this->model->isDisabled()) {
            return true;
        }

        return in_array(Entry::STATUS_DISABLED, [
            $this->entry->status,
            $this->entry->parent_status,
        ]);
    }

    protected function isDraft(): bool
    {
        if ($this->model->isDraft()) {
            return true;
        }

        return in_array(Entry::STATUS_DRAFT, [
            $this->entry->status,
            $this->entry->parent_status,
        ]);
    }

    public static function tag(Entry|Section $model, array $options = []): string
    {
        $link = Yii::$container->get(FrontendLink::class, [
            'model' => $model,
            'options' => $options,
        ]);

        return (string)$link;
    }
}
