<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Cms\Models\Traits\MetaImageTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

/**
 * @extends Asset<Entry>
 */
class EntryAsset extends Asset
{
    use MetaImageTrait;

    #[Override]
    public static function getModelClass(): string
    {
        return Entry::class;
    }

    #[Override]
    public function getAdminRoute(): array|false
    {
        $route = parent::getAdminRoute();
        return $route ? [...$route, ...$this->getAdminTenantRouteParams()] : false;
    }

    /**
     * @return array<string, Tenant>
     */
    public function getAdminTenantRouteParams(): array
    {
        return $this->model->getAdminTenantRouteParams();
    }

    #[Override]
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/entry-asset';
    }

    #[Override]
    public function getAdminType(): string
    {
        return Yii::t('media', 'ASSET_ENTRY_SINGULAR');
    }

    /**
     * @return list<CustomAttribute>
     */
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        $names = ['alt_text', 'loading', 'fetchpriority'];

        return array_values(array_filter(
            parent::getDefaultCustomAttributes(),
            static fn (CustomAttribute $definition): bool => in_array($definition->name, $names, true),
        ));
    }
}
