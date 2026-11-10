<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryUsage;
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
use yii\data\ArrayDataProvider;

/**
 * @extends GridView<EntryUsage>
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

        $this->provider ??= new ArrayDataProvider([
            'allModels' => $this->entry ? EntryUsage::findAll($this->entry) : [],
            'key' => 'id',
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            $this->getModelColumn(),
            $this->getUsageColumn(),
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
            ->url(fn (EntryUsage $usage): ?array => $this->getModelRoute($usage));
    }

    protected function getModelName(EntryUsage $usage): string|Stringable
    {
        return $usage->model instanceof AdminModelInterface
            ? AdminModelHeading::make()->model($usage->model)
            : Html::encode("$usage->model_class #$usage->model_id");
    }

    protected function getUsageColumn(): ?Column
    {
        return DataColumn::make()
            ->property('linkCounts')
            ->title(Yii::t('cms', 'ENTRY_REFERENCE_USAGE_LABEL'))
            ->value($this->getUsageColumnValue(...));
    }

    protected function getUsageColumnValue(EntryUsage $usage): string
    {
        $texts = [];

        if ($usage->listed) {
            $texts[] = Yii::t('cms', 'ENTRY_REFERENCE_LISTED_TEXT');
        }

        if ($usage->linkCounts) {
            $labels = [];

            foreach ($usage->linkCounts as $label => $count) {
                $labels[] = $count > 1 ? "$label ({$count}x)" : $label;
            }

            $texts[] = Yii::t('cms', 'ENTRY_REFERENCE_LINKED_TEXT', ['attributes' => implode(', ', $labels)]);
        }

        return implode(' · ', $texts);
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
    protected function getButtonColumnContent(EntryUsage $usage): array
    {
        $route = $this->getModelRoute($usage);
        return $route ? [ViewGridButton::make()->url($route)] : [];
    }

    /**
     * Only for a record the user may open.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getModelRoute(EntryUsage $usage): ?array
    {
        $model = $usage->model;

        if (!$model instanceof AdminModelInterface || !$this->webuser->can($model->getPermissionName())) {
            return null;
        }

        return $model->getAdminRoute() ?: null;
    }
}
