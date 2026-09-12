<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Media\Models\Asset;
use Override;
use Yii;

/**
 * @property-read Section $model {@see static::getModel()}
 */
class SectionAsset extends Asset
{
    #[Override]
    public static function getModelClass(): string
    {
        return Section::class;
    }

    #[Override]
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/section-asset';
    }

    #[Override]
    public function getPermissionName(string $action): string
    {
        return match ($action) {
            'create' => Section::AUTH_SECTION_ASSET_CREATE,
            'delete' => Section::AUTH_SECTION_ASSET_DELETE,
            'order' => Section::AUTH_SECTION_ASSET_ORDER,
            'update' => Section::AUTH_SECTION_ASSET_UPDATE,
        };
    }

    #[Override]
    public function getModel(): Section
    {
        /** @var Section */
        return parent::getModel();
    }

    #[Override]
    public function getTrailModelType(): string
    {
        return Yii::t('media', 'ASSET_SECTION_ASSET');
    }
}
