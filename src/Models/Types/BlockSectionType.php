<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use BackedEnum;
use Closure;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Helpers\Html;
use Override;
use Yii;

/**
 * A section type that carries nothing but a block: the status, the type and the block are the whole form, and the
 * block answers for the view file, the assets and the linked entries.
 */
class BlockSectionType extends SectionType
{
    public function __construct(int|BackedEnum $value)
    {
        parent::__construct($value);

        $this->allowBlock();
        $this->allowAssets(false);
        $this->allowEntries(false);
        $this->hiddenFields('name', 'content', 'slug');
    }

    #[Override]
    public function getName(): string
    {
        return $this->name ?? Yii::t('cms', 'SECTION_TYPE_BLOCK');
    }

    #[Override]
    public function getGridContent(): Closure|string|null
    {
        return $this->gridContent ?? static fn (Section $section): ?string => $section->block
            ? Html::encode($section->block->getI18nAttribute('name'))
            : null;
    }
}
