<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\ReorderBlocks;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\BlockControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\BlockActiveDataProvider;
use Hirtz\Skeleton\Web\Traits\StatusControllerTrait;
use Hirtz\Skeleton\Widgets\Flashes;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class BlockController extends AbstractController
{
    use BlockControllerTrait;
    use StatusControllerTrait;

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
                        'actions' => ['create', 'delete', 'index', 'order', 'status', 'update'],
                        'roles' => [Block::AUTH_BLOCK],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'order' => ['post'],
                    'status' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(?int $type = null, ?string $q = null): Response|string
    {
        $provider = Yii::$container->get(BlockActiveDataProvider::class, config: [
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $type = null): Response|string
    {
        $block = Block::instantiateFromPost($this->request->post(), $type);
        $block->loadDefaultValues();

        if ($block->load($this->request->post()) && !$this->request->isFormReload() && $block->insert()) {
            $this->success(Yii::t('cms', 'BLOCK_SUCCESS_CREATED'));
            return $this->redirect(['update', 'id' => $block->id]);
        }

        return $this->render('create', [
            'block' => $block,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $block = $this->findBlock($id);

        if ($block->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($block->update()) {
                $this->success(Yii::t('cms', 'BLOCK_SUCCESS_UPDATED'));
            }

            if (!$block->hasErrors()) {
                return $this->refresh();
            }
        }

        return $this->render('update', [
            'block' => $block,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findBlock($id));
    }

    public function actionDelete(int $id): Response|string
    {
        $block = $this->findBlock($id);

        if ($block->delete()) {
            $this->success(Yii::t('cms', 'BLOCK_SUCCESS_DELETED'));
            return $this->redirect(['index']);
        }

        $errors = $block->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors) ?: null);
    }

    public function actionOrder(): string
    {
        if (ReorderBlocks::runWithBodyParam('block')) {
            $this->success(Yii::t('cms', 'BLOCK_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }
}
