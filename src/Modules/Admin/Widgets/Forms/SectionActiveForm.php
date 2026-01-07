<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;
use Yii;
use yii\helpers\Html;

/**
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use SlugFieldTrait;

    public int|false $maxBaseUrlLength = 70;

    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            $this->getStatusField(),
            $this->getTypeField(),
            $this->getNameField(),
            $this->getContentField(),
            $this->getSlugField(),
        ];

        parent::configure();
    }

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

        if ($this->maxBaseUrlLength && strlen((string) $url) > $this->maxBaseUrlLength) {
            $url = Html::tag('span', substr((string) $url, 0, $this->maxBaseUrlLength) . '…#', ['title' => $url]);
        }

        return $url;
    }

    protected function hasSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}
