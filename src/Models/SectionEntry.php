<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Override;
use Yii;

/**
 * @extends EntryRelation<Section>
 */
class SectionEntry extends EntryRelation
{
    #[Override]
    public static function getModelClass(): string
    {
        return Section::class;
    }

    #[Override]
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/section-entry';
    }

    #[Override]
    public function getPermissionName(): string
    {
        return Entry::AUTH_ENTRY;
    }

    #[Override]
    public function getModel(): Section
    {
        /** @var Section */
        return parent::getModel();
    }

    #[Override]
    public function getAdminName(): string
    {
        return Yii::t('cms', 'SECTION_ENTRY_SECTION_ENTRY');
    }
}
