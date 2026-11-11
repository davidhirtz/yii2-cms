<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Widgets\AdminModelHeading;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * @extends GridView<EntryReference>
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryReferenceController::actionIndex()
 */
class EntryReferenceGridView extends GridView
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
        $this->attributes['id'] ??= 'entry-reference-grid-view';

        $this->provider ??= new ActiveDataProvider([
            'query' => EntryReference::find()
                ->andWhere(['entry_id' => $this->entry->id ?? null])
                ->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC]),
            'sort' => false,
        ]);

        EntryReference::populateModelRelations($this->provider->getModels());

        $this->columns ??= [
            $this->getModelColumn(),
            $this->getAttributesColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getModelColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('model_class')
            ->title(Yii::t('cms', 'ENTRY_REFERENCE_MODEL_LABEL'))
            ->value($this->getModelName(...))
            ->format('raw')
            ->url(fn (EntryReference $reference): ?array => $this->getModelRoute($reference));
    }

    protected function getModelName(EntryReference $reference): string|Stringable
    {
        $model = $reference->getModel();

        return $model instanceof AdminModelInterface
            ? AdminModelHeading::make()->model($model)
            : Html::encode("$reference->model_class #$reference->model_id");
    }

    protected function getAttributesColumn(): ?Column
    {
        return DataColumn::make()
            ->property('attribute_counts')
            ->title(Yii::t('cms', 'ENTRY_REFERENCE_ATTRIBUTES_LABEL'))
            ->value($this->getAttributesColumnValue(...));
    }

    protected function getAttributesColumnValue(EntryReference $reference): string
    {
        $labels = [];

        foreach ($reference->getAttributeLabelCounts() as $label => $count) {
            $labels[] = $count > 1 ? "$label ({$count}x)" : $label;
        }

        return implode(', ', $labels);
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
    protected function getButtonColumnContent(EntryReference $reference): array
    {
        $route = $this->getModelRoute($reference);
        return $route ? [ViewGridButton::make()->url($route)] : [];
    }

    /**
     * Only for a record the user may open.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getModelRoute(EntryReference $reference): ?array
    {
        $model = $reference->getModel();

        if (!$model instanceof AdminModelInterface || !$this->webuser->can($model->getPermissionName())) {
            return null;
        }

        return $model->getAdminRoute() ?: null;
    }
}
