<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Block;
use yii\web\NotFoundHttpException;

trait BlockControllerTrait
{
    protected function findBlock(int $id): Block
    {
        $block = Block::findOne($id);

        if (!$block || !$this->isBlockAllowed($block)) {
            throw new NotFoundHttpException();
        }

        return $block;
    }

    protected function isBlockAllowed(Block $block): bool
    {
        return true;
    }
}
