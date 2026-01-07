<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\EntryParentIdFieldTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MetaFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\DateTimeField;
use Override;
use Stringable;
use Yii;

/**
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use EntryParentIdFieldTrait;
    use MetaFieldsTrait;
    use SlugFieldTrait;

    /**
     * @return void
     */
    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getTypeField(),
                $this->getParentIdField(),
                $this->getNameField(),
                $this->getContentField(),
                $this->getPublishDateField(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
            ]
        ];

        parent::configure();
    }

    protected function getPublishDateField(): ?Stringable
    {
        return DateTimeField::make()
            ->property('publish_date');
    }

    protected function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();
        $route = ['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null];
        $url = $this->model->isEnabled() ? $manager->createAbsoluteUrl($route) : $manager->createDraftUrl($route);

        return rtrim($url, '/') . '/';
    }

    protected function hasSlugField(): bool
    {
        return !$this->model->isIndex() || !$this->model->isEnabled();
    }
}
