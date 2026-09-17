<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Media\Models\Asset;
use Override;
use Yii;

/**
 * @extends Asset<Block>
 */
class BlockAsset extends Asset
{
    #[Override]
    public static function getModelClass(): string
    {
        return Block::class;
    }

    #[Override]
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/block-asset';
    }

    #[Override]
    public function getAdminType(): string
    {
        return Yii::t('cms', 'BLOCK_ASSET_BLOCK_ASSET');
    }
}
