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
    /**
     * @var list<string>
     */
    protected array $visibleFields = ['status', 'type', 'block_id'];

    /**
     * @var list<string>|null
     */
    private ?array $sectionFields = null;

    public function __construct(int|BackedEnum $value)
    {
        parent::__construct($value);

        $this->allowBlock();
        $this->allowAssets(false);
        $this->allowEntries(false);
    }

    /**
     * Every other field of a section is hidden, except those of the type's own `customAttributes()`.
     */
    public function visibleFields(string ...$visibleFields): static
    {
        $this->visibleFields = array_values($visibleFields);
        return $this;
    }

    #[Override]
    public function getHiddenFields(): array
    {
        $this->sectionFields ??= Yii::$container->get(Section::class, [], ['customAttributes' => []])
            ->getCustomAttributeNames();

        return array_values(array_unique([
            ...parent::getHiddenFields(),
            ...array_diff($this->sectionFields, $this->visibleFields),
        ]));
    }

    #[Override]
    public function getName(): string
    {
        return $this->name ?? Yii::t('cms', 'SECTION_TYPE_BLOCK_NAME');
    }

    /**
     * A closure group of the block's type expects a section of that type, so the type's view file stands in for it.
     */
    #[Override]
    public function getGroup(): Closure|string|null
    {
        return $this->group ?? static function (Section $section): ?string {
            $type = $section->getVisibleBlock()?->getType();
            $group = $type instanceof SectionType ? $type->getGroup() : null;

            return is_string($group) ? $group : $type?->getViewFile();
        };
    }

    #[Override]
    public function getGridContent(): Closure|string|null
    {
        return $this->gridContent ?? static fn (Section $section): ?string => $section->block
            ? Html::encode($section->block->getI18nAttribute('name'))
            : null;
    }
}
