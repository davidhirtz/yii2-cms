<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\MenuIdsField;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MetaFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ParentIdFieldTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
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
        $this->setTenant();

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        $tenantIdRow = [$this->getTenantIdField()];

        $rows = [
            [
                $this->getStatusField(),
                $this->getTypeField(),
                $this->getParentIdField(),
                $this->getNameField(),
                $this->getPublishDateField(),
            ],
            [
                ...$this->getCustomAttributeFields(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
                $this->getMenuIdsField(),
            ],
        ];

        // With several tenants the field decides which parents and which URL the rest of the form shows.
        return count(TenantCollection::getAll()) > 1
            ? [$tenantIdRow, ...$rows]
            : [...$rows, $tenantIdRow];
    }

    /**
     * The record answers first: it is an existing entry, or a new one carrying the tenant the form reload posted.
     * Only a record with none falls back to the request, which is what a `tenant` parameter on the create route and
     * the admin's own host are for.
     */
    protected function setTenant(): void
    {
        $tenant = TenantCollection::getById($this->model->tenant_id);

        $tenant ??= TenantCollection::getFromRequest();

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

    protected function getMenuIdsField(): ?Stringable
    {
        return MenuIdsField::make();
    }

    #[Override]
    protected function createParentIdSelectField(): SelectField
    {
        return EntryParentIdSelectField::make();
    }

    #[Override]
    protected function hasParentIdField(): bool
    {
        return $this->model->allowsParent();
    }

    /**
     * The prefix is the parent's path rather than its route: an entry being created under a parent that has no
     * children yet is exactly the case {@see Entry::getRoute()} answers `false` for.
     */
    #[Override]
    protected function getSlugBaseUrl(?string $language = null): string
    {
        return Yii::$app->getI18n()->callback($language ?? Yii::$app->language, function (): string {
            $manager = Yii::$app->getUrlManager();
            $parent = $this->model->parent_id ? $this->model->parent : null;
            $params = $this->getSlugBaseRouteParams();
            $slug = $parent?->getFormattedSlug();

            $route = $slug
                ? ['/cms/site/view', 'slug' => $slug, ...$params]
                : ['/cms/site/index', ...$params];

            $url = $this->model->isEnabled() && ($parent?->isEnabled() ?? true)
                ? $manager->createAbsoluteUrl($route)
                : $manager->createDraftUrl($route);

            return rtrim($url, '/') . '/';
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSlugBaseRouteParams(): array
    {
        return $this->model->getTenantRouteParams();
    }

    protected function hasSlugField(): bool
    {
        return !$this->model->isIndex() || !$this->model->isEnabled();
    }
}
