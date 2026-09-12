<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Yii;

/**
 * @property bool|int $show_in_footer
 * @mixin Entry
 */
trait FooterAttributeTrait
{
    public function getFooterAttributeRules(): array
    {
        return [
            [
                ['show_in_footer'],
                'boolean',
            ],
        ];
    }

    public function getFooterAttributeLabels(): array
    {
        return [
            'show_in_footer' => Yii::t('cms', 'FOOTER_ATTRIBUTE_SHOW_IN_FOOTER'),
        ];
    }

    public function hasShowInFooterEnabled(): bool
    {
        return $this->getTypeOptions()['hasShowInFooterEnabled'] ?? true;
    }

    public function isFooterItem(): bool
    {
        return (bool)$this->show_in_footer;
    }
}
