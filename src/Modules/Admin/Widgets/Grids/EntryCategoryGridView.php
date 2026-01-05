<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits\CategoryGridTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use yii\db\ActiveRecordInterface;

/**
 * @template T of Category
 * @extends GridView<T>
 * @property CategoryActiveDataProvider $provider
 */
class EntryCategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    public string $categoryParamName = 'category';
    public bool $showUrl = false;

    #[Override]
    public function configure(): void
    {
        $this->initAncestors();

        $this->rowAttributes ??= fn (Category $category) => [
            'class' => $category->entryCategory ? 'is-selected' : null,
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getBranchCountColumn(),
            $this->getEntryCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
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

    protected function getButtonColumnContent(Category $category): array
    {
        // Categories can always be removed even, if they were not supposed to have entries enabled
        if (!$category->hasEntriesEnabled() && !$category->entryCategory) {
            return [];
        }

        return [
            Button::make()
                ->primary()
                ->icon($category->entryCategory ? 'ban' : 'star')
                ->post([
                    $category->entryCategory ? 'delete' : 'create',
                    'entry' => $this->provider->entry->id,
                    'category' => $category->id,
                ]),
        ];
    }

    /**
     * @param T $model
     */
    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['category/update', 'id' => $model->id];
    }
}
