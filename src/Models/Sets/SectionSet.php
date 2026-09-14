<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Sets;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Models\Definitions\Definition;
use Override;
use yii\base\InvalidConfigException;

/**
 * A set of sections an entry can be given in one go, declared by {@see Section::getSectionSets()}.
 */
class SectionSet extends Definition
{
    /**
     * @var list<SectionTemplate>
     */
    protected array $sections = [];

    public function sections(SectionTemplate ...$sections): static
    {
        $this->sections = array_values($sections);
        return $this;
    }

    /**
     * @return list<SectionTemplate>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    #[Override]
    public function validate(string $modelClass): void
    {
        parent::validate($modelClass);

        if (!is_a($modelClass, Section::class, true)) {
            throw new InvalidConfigException("{$this->getDisplayValue()} is declared by $modelClass, which is not a " . Section::class . '.');
        }

        if (!$this->sections) {
            throw new InvalidConfigException("{$this->getDisplayValue()} of $modelClass declares no sections.");
        }

        $types = $modelClass::getTypeDefinitions();

        foreach ($this->sections as $section) {
            if (!isset($types[$section->type])) {
                throw new InvalidConfigException("{$this->getDisplayValue()} of $modelClass names the undeclared section type $section->type.");
            }
        }
    }
}
