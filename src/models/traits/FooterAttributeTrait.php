<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Entry;
use Yii;

/**
 * @property bool|int $show_in_footer
 * @mixin Entry
 */
trait FooterAttributeTrait
{
    public function getFooterAttributeTraitRules(): array
    {
        return [
            [
                ['show_in_footer'],
                'boolean',
            ],
        ];
    }

    public function getFooterAttributeTraitAttributeLabels(): array
    {
        return [
            'show_in_footer' => Yii::t('cms', 'Show in footer'),
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
