<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\helpers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;
use Yii;

class FrontendLink implements Stringable
{
    protected readonly ?Entry $entry;

    public function __construct(
        protected readonly Category|Entry|Section $model,
        protected array $options = [],
    ) {
        if ($this->model instanceof Section) {
            $this->entry = $this->model->entry;
        } elseif ($this->model instanceof Entry) {
            $this->entry = $this->model;
        } else {
            $this->entry = null;
        }
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
        if ($this->entry) {
            return in_array(Entry::STATUS_DISABLED, [
                $this->entry->status,
                $this->entry->parent_status,
            ]);
        }

        return $this->model->isDisabled();
    }

    protected function isDraft(): bool
    {
        if ($this->entry) {
            return in_array(Entry::STATUS_DRAFT, [
                $this->entry->status,
                $this->entry->parent_status,
            ]);
        }

        return $this->model->isDraft();
    }

    public static function tag(Category|Entry|Section $model, array $options = []): string
    {
        $link = Yii::$container->get(FrontendLink::class, [
            'model' => $model,
            'options' => $options,
        ]);

        return (string)$link;
    }
}
