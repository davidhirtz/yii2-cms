<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\I18nActiveQuery;

/**
 * @template T of Section
 * @template-extends I18nActiveQuery<Section>
 */
class SectionQuery extends I18nActiveQuery
{
    /**
     * Override this method to select only the attributes needed for frontend display.
     */
    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(), [
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ])));
    }
}
