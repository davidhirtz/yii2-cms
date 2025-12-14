<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Section;

/**
 * @property int|null $section_id
 * @property-read Section|null $section {@see static::getSection()}
 */
trait SectionRelationTrait
{
    public function getSection(): SectionQuery
    {
        /** @var SectionQuery $relation */
        $relation = $this->hasOne(Section::class, ['id' => 'section_id']);
        return $relation;
    }

    public function populateSectionRelation(?Section $section): void
    {
        $this->populateRelation('section', $section);
        $this->section_id = $section?->id;
    }
}
