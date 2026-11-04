<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\CustomAttributes;

use Hirtz\Skeleton\Models\CustomAttributes\BooleanCustomAttribute;
use Override;
use Yii;

class NoIndexCustomAttribute extends BooleanCustomAttribute
{
    public function __construct(string $name = 'noindex')
    {
        parent::__construct($name);
    }

    #[Override]
    public function getLabel(): string
    {
        return $this->label ?? Yii::t('cms', 'ENTRY_NO_INDEX_LABEL');
    }

    #[Override]
    public function getHint(): ?string
    {
        return $this->hint ?? Yii::t('cms', 'ENTRY_NO_INDEX_HINT');
    }
}
