<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Section;

/**
 * Class SectionQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Section one($db = null)
 */
class SectionQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return SectionQuery
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'updated_at', 'created_at']));
    }
}