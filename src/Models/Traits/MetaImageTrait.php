<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\I18n\Lang;

trait MetaImageTrait
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMetaImageTypeOptions(): array
    {
        $hiddenFields = array_diff(static::instance()->attributes(), [
            'status',
            'type',
        ]);

        return [
            static::TYPE_META_IMAGE => [
                'name' => Lang::t('cms', 'META_IMAGE_META_IMAGE'),
                'hiddenFields' => $hiddenFields,
                'visible' => fn (Asset $asset): bool => $asset instanceof EntryAsset,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getTypes(): array
    {
        return static::getViewportTypes() + static::getMetaImageTypeOptions();
    }
}
