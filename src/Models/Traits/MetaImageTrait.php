<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Types\AssetType;
use Yii;

trait MetaImageTrait
{
    /**
     * @return list<AssetType>
     */
    public static function getMetaImageTypes(): array
    {
        $hiddenFields = array_diff(static::instance()->attributes(), [
            'status',
            'type',
        ]);

        return [
            AssetType::make(static::TYPE_META_IMAGE)
                ->name(Yii::t('cms', 'META_IMAGE_META_IMAGE'))
                ->hiddenFields(...$hiddenFields)
                ->available(static fn (Asset $asset): bool => $asset instanceof EntryAsset),
        ];
    }

    /**
     * @return list<AssetType>
     */
    public static function getTypes(): array
    {
        return [...static::getViewportTypes(), ...static::getMetaImageTypes()];
    }
}
