<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\IconCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;

/**
 * @property string|null $subtitle
 * @property string|null $subtitle_de
 * @property list<array>|null $links
 */
class TestSection extends Section
{
    public const int TYPE_HEADLINE = 1;
    public const int TYPE_TEXT_COLUMN = 2;
    public const int TYPE_GALLERY = 3;
    public const int TYPE_BLOG = 4;
    public const int TYPE_LINK_LIST = 5;

    #[\Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_HEADLINE)
                ->name('Headline')
                ->hiddenFields('content', self::FIELD_ENTRIES)
                ->customAttributes(fn (): array => [
                    TextCustomAttribute::make('subtitle')
                        ->translatable(),
                ]),
            SectionType::make(self::TYPE_TEXT_COLUMN)
                ->name('Column')
                ->hiddenFields('name', self::FIELD_ASSETS, self::FIELD_ENTRIES),
            SectionType::make(self::TYPE_GALLERY)
                ->name('Gallery')
                ->hiddenFields('name', 'content', self::FIELD_ENTRIES),
            SectionType::make(self::TYPE_BLOG)
                ->name('Blog')
                ->entriesOrderBy(['position' => SORT_ASC])
                ->hiddenFields('name', 'content', self::FIELD_ASSETS),
            SectionType::make(self::TYPE_LINK_LIST)
                ->name('Link list')
                ->hiddenFields('content', self::FIELD_ASSETS, self::FIELD_ENTRIES)
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->maxCount(5)
                        ->attributes([
                            TextCustomAttribute::make('label')
                                ->translatable(),
                            UrlCustomAttribute::make('url')
                                ->required(),
                            IconCustomAttribute::make('icon'),
                        ]),
                ]),
        ];
    }
}
