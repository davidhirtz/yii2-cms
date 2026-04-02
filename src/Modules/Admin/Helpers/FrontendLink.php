<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Helpers;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Html\A;
use Stringable;
use Yii;

readonly class FrontendLink implements Stringable
{
    protected ?Entry $entry;

    public function __construct(protected Category|Entry|Section $model)
    {
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

        return A::make()
            ->class($this->isDisabled() ? 'text-invalid' : null)
            ->text($url)
            ->href($url)
            ->target('_blank')
            ->render();
    }

    protected function isDisabled(): bool
    {
        if ($this->model->isDisabled()) {
            return true;
        }

        if ($this->entry) {
            return in_array(Entry::STATUS_DISABLED, [
                $this->entry->status,
                $this->entry->parent_status,
            ], true);
        }

        return false;
    }

    protected function isDraft(): bool
    {
        if ($this->model->isDraft()) {
            return true;
        }

        if ($this->entry) {
            return in_array(Entry::STATUS_DRAFT, [
                $this->entry->status,
                $this->entry->parent_status,
            ], true);
        }

        return false;
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
