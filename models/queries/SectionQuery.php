<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class SectionQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Section[] all($db = null)
 * @method Section one($db = null)
 */
class SectionQuery extends ActiveQuery
{
    /**
     * Override this method to select only the attributes needed for frontend display.
     * @return $this
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'updated_at', 'created_at'])));
    }
}