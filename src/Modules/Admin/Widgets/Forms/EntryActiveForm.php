<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MetaFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ParentIdFieldTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\DateTimeField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;
use Yii;

/**
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use MetaFieldsTrait;
    use ModuleTrait;
    use ParentIdFieldTrait;
    use SlugFieldTrait;

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
            ],
        ];

        parent::configure();
    }

    protected function getPublishDateField(): ?Stringable
    {
        return DateTimeField::make()
            ->property('publish_date');
    }

    #[Override]
    protected function createParentIdSelectField(): SelectField
    {
        return EntryParentIdSelectField::make();
    }

    #[Override]
    protected function hasParentIdField(): bool
    {
        return static::getModule()->enableNestedEntries && $this->model->hasParentEnabled();
    }

    #[Override]
    protected function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();

        $route = [
            '/cms/site/index',
            ...$this->model->getRouteParams(),
            'language' => $manager->i18nUrl ? $language : null,
            'slug' => null,
        ];

        $url = $this->model->isEnabled() ? $manager->createAbsoluteUrl($route) : $manager->createDraftUrl($route);

        return rtrim($url, '/') . '/';
    }

    protected function hasSlugField(): bool
    {
        return !$this->model->isIndex() || !$this->model->isEnabled();
    }
}
