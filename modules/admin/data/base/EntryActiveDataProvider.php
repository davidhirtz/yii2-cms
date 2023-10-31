<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;

/**
 * EntryActiveDataProvider implements a data provider based on {@see Entry::find()}. To make changes, override the parent
 * class {@see \davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider}.
 *
 * @property EntryQuery $query
 * @method Entry[] getModels()
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var Category|null the category to filter by
     */
    public ?Category $category = null;

    /**
     * @var string|null
     */
    public ?string $searchString = null;

    /**
     * @var int|null the entry type
     */
    public ?int $type = null;

    public function init(): void
    {
        $this->query = $this->query ?: Entry::find();
        parent::init();
    }

    public function prepareQuery(): void
    {
        $this->initQuery();
        parent::prepareQuery();
    }

    protected function initQuery(): void
    {
        if (static::getModule()->defaultEntryOrderBy) {
            $this->query->orderBy(static::getModule()->defaultEntryOrderBy);
        }

        if ($type = (Entry::getTypes()[$this->type] ?? false)) {
            if (isset($type['orderBy'])) {
                $this->query->orderBy($type['orderBy']);
            }

            if (isset($type['sort'])) {
                $this->setSort($type['sort']);
            }

            $this->query->andWhere([Entry::tableName() . '.[[type]]' => $this->type]);
        }

        if ($this->category) {
            $this->query->whereCategory($this->category);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    public function getPagination(): Pagination|false
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    public function getSort(): Sort|false
    {
        return !$this->isOrderedByPosition() ? parent::getSort() : false;
    }

    /**
     * @param array|bool|Sort $value
     */
    public function setSort($value): void
    {
        // Try to set the default order from the query if it's a single order.
        if (is_array($value) && is_array($this->query->orderBy) && count($this->query->orderBy) === 1) {
            $value['defaultOrder'] ??= $this->query->orderBy;
        }

        parent::setSort($value);
    }

    public function isOrderedByPosition(): bool
    {
        return isset($this->query->orderBy) && in_array(key($this->query->orderBy), [
                EntryCategory::tableName() . '.[[position]]',
                'position',
            ]);
    }
}