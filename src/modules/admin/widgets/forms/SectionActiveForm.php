<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
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
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'type',
            'name',
            'content',
            'slug',
        ];

        parent::init();
    }

    public function slugField(array $options = []): ActiveField|string
    {
        return $this->showSlugField() ? parent::slugField($options) : '';
    }

    public function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();

        $route = array_merge($this->model->entry->getRoute(), [
            'language' => $manager->hasI18nUrls() ? $language : null, '#' => '',
        ]);

        $isDraft = in_array(Entry::STATUS_DRAFT, [
            $this->model->entry->status,
            $this->model->entry->parent_status,
        ]);

        $url = $isDraft ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

        if ($this->maxBaseUrlLength && strlen((string) $url) > $this->maxBaseUrlLength) {
            $url = Html::tag('span', substr((string) $url, 0, $this->maxBaseUrlLength) . '…#', ['title' => $url]);
        }

        return $url;
    }

    protected function showSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}
