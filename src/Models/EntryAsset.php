<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Cms\Models\Traits\MetaImageTrait;
use Hirtz\Media\Models\Asset;
use Override;
use Yii;

/**
 * @property-read Entry $model {@see static::getModel()}
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
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/entry-asset';
    }

    #[Override]
    public function getPermissionName(string $action): string
    {
        return match ($action) {
            'create' => Entry::AUTH_ENTRY_ASSET_CREATE,
            'delete' => Entry::AUTH_ENTRY_ASSET_DELETE,
            'order' => Entry::AUTH_ENTRY_ASSET_ORDER,
            'update' => Entry::AUTH_ENTRY_ASSET_UPDATE,
        };
    }

    #[Override]
    public function getModel(): Entry
    {
        /** @var Entry */
        return parent::getModel();
    }

    #[Override]
    public function getTrailModelType(): string
    {
        return Yii::t('media', 'ASSET_ENTRY_ASSET');
    }
}
