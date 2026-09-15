<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;
use Yii;

/**
 * @property bool|int $show_in_footer
 * @mixin Entry
 */
trait FooterAttributeTrait
{
    /**
     * @return list<array<mixed>>
     */
    public function getFooterAttributeRules(): array
    {
        return [
            [
                ['show_in_footer'],
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getFooterAttributeLabels(): array
    {
        return [
            'show_in_footer' => Yii::t('cms', 'FOOTER_ATTRIBUTE_SHOW_IN_FOOTER'),
        ];
    }

    public function hasShowInFooterEnabled(): bool
    {
        $type = $this->getType();
        return !$type instanceof EntryType || $type->hasShowInFooterEnabled();
    }

    public function isFooterItem(): bool
    {
        return (bool)$this->show_in_footer;
    }
}
