<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\DeleteSections;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\BlockControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\BlockSectionGridView;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The sections that place one block, which is what makes a block safe to change or delete. Removing one here
 * stays here — the section's own controller would redirect to the entry it belongs to, which is not where the
 * user was.
 */
class BlockSectionController extends AbstractController
{
    use BlockControllerTrait;
    use SectionControllerTrait;

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
                        'actions' => ['delete', 'delete-all', 'index'],
                        'roles' => [Block::AUTH_BLOCK],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'delete-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(?int $block = null): Response|string
    {
        if (!$block) {
            throw new NotFoundHttpException();
        }

        return $this->render('index', [
            'block' => $this->findBlock($block),
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $section = $this->findBlockSection($id);
        $block = $this->findBlock($section->block_id);

        $section->delete();
        $this->errorOrSuccess($section, Yii::t('cms', 'SECTION_SUCCESS_DELETED'));

        return $this->redirectToBlock($block);
    }

    /**
     * @see BlockSectionGridView::getDeleteSelectionRoute()
     */
    public function actionDeleteAll(int $block): Response
    {
        $block = $this->findBlock($block);
        $sections = $this->findBlockSections($block);

        if (!$sections) {
            return $this->redirect(['/admin/cms/block/update', 'id' => $block->id]);
        }

        $action = DeleteSections::create($sections);

        if ($count = count($action->getDeleted())) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_SELECTED_DELETED', ['count' => $count]));
        }

        foreach ($action->getFailed() as $section) {
            $this->error($section);
        }

        return $this->redirectToBlock($block);
    }

    /**
     * The tab goes with the block's last section, so the redirect has to leave it.
     */
    protected function redirectToBlock(Block $block): Response
    {
        $block->refresh();

        return $this->redirect($block->section_count
            ? ['index', 'block' => $block->id]
            : ['/admin/cms/block/update', 'id' => $block->id]);
    }

    /**
     * Scoped to the block the selection was made on, so a crafted id cannot reach a section of another one.
     *
     * @return list<Section>
     */
    protected function findBlockSections(Block $block): array
    {
        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            throw new ForbiddenHttpException();
        }

        $ids = array_map(intval(...), $this->request->post('selection', []));

        return $ids
            ? array_values(Section::findAll(['id' => $ids, 'block_id' => $block->id]))
            : [];
    }

    /**
     * Deleting a section is the section's business, so its own permission decides — this controller lists it, the
     * entry owns it.
     */
    protected function findBlockSection(int $id): Section
    {
        $section = $this->findSection($id);

        if (!$section->block_id) {
            throw new NotFoundHttpException();
        }

        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            throw new ForbiddenHttpException();
        }

        return $section;
    }
}
