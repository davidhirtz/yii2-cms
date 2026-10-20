<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Override;
use Yii;

/**
 * @extends Asset<Block>
 */
class BlockAsset extends Asset
{
    /**
     * The fields of the container's section asset class, not its type's; their closures receive the block asset.
     *
     * @return list<CustomAttribute>
     */
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        $asset = Yii::$container->get(SectionAsset::class, [], [
            'customAttributes' => [],
            'translatableAttributes' => $this->translatableAttributes,
        ]);

        return $asset->getCustomAttributes();
    }

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
