<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Actions\ReorderEntryRelations;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Modules\Admin\Controllers\AbstractController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Flashes;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The four actions every model that links entries needs. A controller using it names the model it is scoped to,
 * the way the media asset controllers do.
 *
 * @mixin AbstractController
 */
trait EntryRelationControllerTrait
{
    use ModuleTrait;

    /**
     * @return array{class: class-string, actions: array<string, list<string>>}
     */
    protected function getEntryRelationVerbs(): array
    {
        return [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['post'],
                'order' => ['post'],
            ],
        ];
    }

    protected function renderEntryRelationIndex(EntryRelationModelInterface $model): Response|string
    {
        $provider = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'relatedModel' => $model,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    protected function createEntryRelation(
        EntryRelationModelInterface $model,
        ?int $entry,
        ?int $category,
        ?int $parent,
        ?string $q,
        ?int $type,
    ): Response|string {
        if ($this->request->getIsPost()) {
            $entryRelation = $this->instantiateEntryRelation($model);
            $entryRelation->populateEntryRelation($this->findEntry((int)$entry));
            $entryRelation->insert();

            $this->errorOrSuccess($entryRelation, Yii::t('cms', 'ENTRY_RELATION_SUCCESS_ADDED'));
        }

        if (!$type && static::getModule()->defaultEntryType) {
            return $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'relatedModel' => $model,
            'innerJoinRelatedModel' => false,
            'category' => Category::findOne($category),
            'parent' => Entry::findOne($parent),
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('create', [
            'provider' => $provider,
        ]);
    }

    protected function deleteEntryRelation(EntryRelationModelInterface $model, int $entry): Response|string
    {
        $entryRelation = $model->getEntryRelations()
            ->andWhere(['entry_id' => $entry])
            ->one();

        if (!$entryRelation) {
            throw new NotFoundHttpException();
        }

        if (!$this->webuser->can($entryRelation->getPermissionName())) {
            throw new ForbiddenHttpException();
        }

        $entryRelation->populateModelRelation($model);
        $entryRelation->delete();

        $this->errorOrSuccess($entryRelation, Yii::t('cms', 'ENTRY_RELATION_SUCCESS_REMOVED'));
        return $this->redirect(['index', $model->getParamName() => $model->id]);
    }

    protected function reorderEntryRelations(EntryRelationModelInterface $model): string
    {
        $success = ReorderEntryRelations::runWithBodyParam('entry-relation', [
            'model' => $model,
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }

    protected function instantiateEntryRelation(EntryRelationModelInterface $model): EntryRelation
    {
        $entryRelation = $model->getEntryRelationClass()::create();
        $entryRelation->populateModelRelation($model);

        return $entryRelation;
    }
}
