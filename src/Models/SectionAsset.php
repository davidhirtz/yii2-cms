<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Media\Models\Asset;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

/**
 * @extends Asset<Section>
 */
class SectionAsset extends Asset
{
    #[Override]
    public static function getModelClass(): string
    {
        return Section::class;
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
        return '/admin/cms/section-asset';
    }

    #[Override]
    public function getAdminType(): string
    {
        return Yii::t('media', 'ASSET_SECTION_SINGULAR');
    }
}
