<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryReference;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\ModelReferenceGridTrait;
use Override;
use yii\data\ActiveDataProvider;

/**
 * @extends GridView<EntryReference>
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryReferenceController::actionIndex()
 */
class EntryReferenceGridView extends GridView
{
    use ModelReferenceGridTrait;

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

        EntryReference::populateModelRelations($this->provider->getModels(), searchable: true);

        $this->columns ??= $this->getModelReferenceColumns();

        parent::configure();
    }
}
