<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Override;
use Yii;
use yii\helpers\Html;
use yii\widgets\ActiveField;

/**
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    public int|false $maxBaseUrlLength = 70;

    /**
     * @uses static::statusField()
     * @uses static::typeField()
     * @uses static::contentField()
     * @uses static::slugField()
     */
    #[Override]
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'type',
            'name',
            'content',
            'slug',
        ];
    }

    #[Override]
    public function slugField(array $options = []): ActiveField|string
    {
        return $this->showSlugField() ? parent::slugField($options) : '';
    }

    #[Override]
    public function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();

        $route = [
            ...$this->model->entry->getRoute(),
            'language' => $manager->hasI18nUrls() ? $language : null,
            '#' => '',
        ];

        $isDraft = in_array(Entry::STATUS_DRAFT, [
            $this->model->entry->status,
            $this->model->entry->parent_status,
        ], true);

        $url = $isDraft ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

        if ($this->maxBaseUrlLength && strlen((string)$url) > $this->maxBaseUrlLength) {
            $url = Html::tag('span', substr((string)$url, 0, $this->maxBaseUrlLength) . '…#', ['title' => $url]);
        }

        return $url;
    }

    protected function showSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}
