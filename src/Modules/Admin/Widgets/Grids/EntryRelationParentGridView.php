<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Skeleton\Widgets\AdminModelHeading;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * The sections and blocks listing an entry, each leading to its list of related entries.
 *
 * @extends GridView<EntryRelation>
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryRelationController::actionIndex()
 */
class EntryRelationParentGridView extends GridView
{
    protected ?Entry $entry = null;

    protected string $layout = '{items}{pager}{footer}';

    public function entry(Entry $entry): static
    {
        $this->entry = $entry;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'entry-relation-parent-grid-view';

        $this->provider ??= new ArrayDataProvider([
            'allModels' => $this->findRelations(),
            'key' => 'id',
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            $this->getModelColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    /**
     * One query per owner class; a relation whose owner is gone is left out.
     *
     * @return list<EntryRelation>
     */
    protected function findRelations(): array
    {
        if (!$this->entry) {
            return [];
        }

        $relations = EntryRelation::find()
            ->where(['entry_id' => $this->entry->id])
            ->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $modelIds = [];

        foreach ($relations as $relation) {
            $modelIds[$relation->model_class][] = $relation->model_id;
        }

        $models = [];

        foreach ($modelIds as $modelClass => $ids) {
            $models[$modelClass] = Yii::createObject($modelClass)::find()
                ->andWhere(['id' => $ids])
                ->indexBy('id')
                ->all();
        }

        $result = [];

        foreach ($relations as $relation) {
            $model = $models[$relation->model_class][$relation->model_id] ?? null;

            if ($model instanceof EntryRelationModelInterface) {
                $relation->populateModelRelation($model);
                $relation->populateEntryRelation($this->entry);
                $result[] = $relation;
            }
        }

        return $result;
    }

    protected function getModelColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('model_class')
            ->title(Yii::t('cms', 'ENTRY_RELATION_MODEL_LABEL'))
            ->value(fn (EntryRelation $relation): Stringable => AdminModelHeading::make()->model($relation->model))
            ->format('raw')
            ->url($this->getRelationsRoute(...));
    }

    protected function getUpdatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(EntryRelation $relation): array
    {
        $route = $this->getRelationsRoute($relation);
        return $route ? [ViewGridButton::make()->url($route)] : [];
    }

    /**
     * The owner's related entries, only with its permission.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getRelationsRoute(EntryRelation $relation): ?array
    {
        if (!$this->webuser->can($relation->getPermissionName())) {
            return null;
        }

        return [
            $relation::getAdminControllerRoute() . '/index',
            $relation->model->getParamName() => $relation->model_id,
        ];
    }
}
