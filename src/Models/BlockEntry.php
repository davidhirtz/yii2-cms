<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Override;
use Yii;

/**
 * @extends EntryRelation<Block>
 */
class BlockEntry extends EntryRelation
{
    #[Override]
    public static function getModelClass(): string
    {
        return Block::class;
    }

    #[Override]
    public static function getAdminControllerRoute(): string
    {
        return '/admin/cms/block-entry';
    }

    #[Override]
    public function getModel(): Block
    {
        /** @var Block */
        return parent::getModel();
    }

    #[Override]
    public function getAdminName(): string
    {
        return Yii::t('cms', 'BLOCK_ENTRY_BLOCK_ENTRY');
    }
}
