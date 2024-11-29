<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

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
        $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
        $urlManager = Yii::$app->getUrlManager();

        $route = array_merge($this->model->entry->getRoute(), [
            'language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null, '#' => '',
        ]);

        $url = $this->model->entry->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);

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
