<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\BlockControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryRelationControllerTrait;
use Override;
use yii\filters\AccessControl;
use yii\web\Response;

class BlockEntryController extends AbstractController
{
    use BlockControllerTrait;
    use EntryControllerTrait;
    use EntryRelationControllerTrait;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'delete', 'index', 'order'],
                        'roles' => [Block::AUTH_BLOCK],
                    ],
                ],
            ],
            'verbs' => $this->getEntryRelationVerbs(),
        ];
    }

    public function actionIndex(int $block): Response|string
    {
        return $this->renderEntryRelationIndex($this->findBlock($block));
    }

    public function actionCreate(
        int $block,
        ?int $entry = null,
        ?int $category = null,
        ?int $parent = null,
        ?string $q = null,
        ?int $type = null
    ): Response|string {
        return $this->createEntryRelation($this->findBlock($block), $entry, $category, $parent, $q, $type);
    }

    public function actionDelete(int $block, int $entry): Response|string
    {
        return $this->deleteEntryRelation($this->findBlock($block), $entry);
    }

    public function actionOrder(int $block): string
    {
        return $this->reorderEntryRelations($this->findBlock($block));
    }

    protected function isBlockAllowed(Block $block): bool
    {
        return $block->allowsEntries();
    }
}
