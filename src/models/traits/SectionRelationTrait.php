<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\Section;

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
