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
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Hirtz\Skeleton\Widgets\Forms\Fields\DateTimeField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\TenantIdField;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Stringable;
use Yii;

/**
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use CustomAttributeFieldsTrait;
    use MetaFieldsTrait;
    use ModuleTrait;
    use ParentIdFieldTrait;
    use SlugFieldTrait;

    #[Override]
    protected function configure(): void
    {
        $this->setTenantFromRequest();

        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getTypeField(),
                $this->getParentIdField(),
                $this->getNameField(),
                $this->getContentField(),
                $this->getPublishDateField(),
                ...$this->getCustomAttributeFields(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
            ],
        ];

        $tenantIdRow = [$this->getTenantIdField()];
        $rows = $this->getRowsAsGroups();

        // With several tenants the field decides which parents and which URL the rest of the form shows.
        $this->rows = count(TenantCollection::getAll()) > 1
            ? [$tenantIdRow, ...$rows]
            : [...$rows, $tenantIdRow];

        parent::configure();
    }

    /**
     * `rows` may be a flat list of fields; adding a row of our own has to keep that shape valid.
     *
     * @return array<int, mixed>
     */
    protected function getRowsAsGroups(): array
    {
        $first = current($this->rows);

        if ($first === false) {
            return [];
        }

        return is_array($first) || $first instanceof Fieldset ? $this->rows : [$this->rows];
    }

    protected function setTenantFromRequest(): void
    {
        $tenant = TenantCollection::getFromRequest();

        if (null === $tenant) {
            $manager = Yii::$app->getUrlManager();
            $tenant = $manager instanceof UrlManager ? $manager->tenant : null;
        }

        $this->model->populateTenantRelation($tenant ?? TenantCollection::getDefault());
    }

    protected function getTenantIdField(): ?Stringable
    {
        return TenantIdField::make();
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
            ...$this->getSlugBaseRouteParams(),
            'language' => $manager->i18nUrl ? $language : null,
        ];

        $url = $this->model->isEnabled() ? $manager->createAbsoluteUrl($route) : $manager->createDraftUrl($route);

        return rtrim($url, '/') . '/';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSlugBaseRouteParams(): array
    {
        return $this->model->getTenantRouteParams();
    }

    protected function getParentIdAttributes(): array
    {
        $attributes = [];

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
            $attributes['data-form-target'][] = '#' . $this->getSlugId($language);
            $attributes['promptAttributes']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        return $attributes;
    }

    protected function hasSlugField(): bool
    {
        return !$this->model->isIndex() || !$this->model->isEnabled();
    }
}
