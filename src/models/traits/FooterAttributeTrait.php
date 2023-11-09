<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Entry;
use Yii;

/**
 * @property bool $show_in_footer
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
        return $this->show_in_footer;
    }
}