<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Media\Models\Asset;
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
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/section-asset';
    }

    #[Override]
    public function getPermissionName(): string
    {
        return Entry::AUTH_ENTRY;
    }

    #[Override]
    public function getAdminType(): string
    {
        return Yii::t('media', 'ASSET_SECTION_ASSET');
    }
}
