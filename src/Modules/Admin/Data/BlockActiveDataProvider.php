<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Queries\BlockQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Override;
use yii\data\Sort;

/**
 * @property BlockQuery<Block> $query
 * @extends ActiveDataProvider<Block>
 */
class BlockActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    public ?string $searchString = null;
    public ?int $type = null;

    public function __construct($config = [])
    {
        $this->query = Block::find();
        parent::__construct($config);
    }

    #[Override]
    protected function prepareQuery(): void
    {
        $this->initQuery();
        parent::prepareQuery();
    }

    /**
     * The order belongs to the `Sort`, not to the query: `yii\data\ActiveDataProvider::prepareModels()` *adds*
     * the sort to whatever the query already carries, so an `orderBy()` here would win over every column header.
     *
     * @param array<string, mixed>|Sort|bool $value
     */
    #[Override]
    public function setSort($value): void
    {
        if (is_array($value)) {
            $value['defaultOrder'] ??= ['updated_at' => SORT_DESC];
        }

        parent::setSort($value);
    }

    protected function initQuery(): void
    {
        if ($this->type) {
            $this->query->andWhere([Block::tableName() . '.[[type]]' => $this->type]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }
}
