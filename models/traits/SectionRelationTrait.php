<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\queries\SectionQuery;

/**
 * @property int|null $section_id
 * @property-read Section $section {@see static::getSection()}
 */
trait SectionRelationTrait
{
    public function getSection(): SectionQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Section::class, ['id' => 'section_id']);
    }

    public function populateSectionRelation(?Section $section): void
    {
        $this->populateRelation('section', $section);
        $this->section_id = $section?->id;
    }
}