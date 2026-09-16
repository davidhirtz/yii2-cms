<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\BlockControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
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
                        'actions' => ['delete', 'index'],
                        'roles' => [Block::AUTH_BLOCK],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
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

        $block->refresh();

        return $this->redirect($block->section_count
            ? ['index', 'block' => $block->id]
            : ['/admin/cms/block/update', 'id' => $block->id]);
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
