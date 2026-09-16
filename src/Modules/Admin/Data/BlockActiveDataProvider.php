<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Queries\BlockQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Override;

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

    protected function initQuery(): void
    {
        $this->query->orderBy(['position' => SORT_ASC]);

        if ($this->type) {
            $this->query->andWhere([Block::tableName() . '.[[type]]' => $this->type]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }
}
