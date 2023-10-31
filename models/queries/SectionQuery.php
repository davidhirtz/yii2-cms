<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * @method Section[] all($db = null)
 * @method Section[] each($batchSize = 100, $db = null)
 * @method Section one($db = null)
 */
class SectionQuery extends ActiveQuery
{
    /**
     * Override this method to select only the attributes needed for frontend display.
     */
    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'updated_at', 'created_at'])));
    }
}