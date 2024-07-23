<?php

namespace davidhirtz\yii2\cms\modules\admin\data;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;

/**
 * @property EntryQuery|null $query
 * @method Entry[] getModels()
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var Section|null the section to filter by
     */
    public ?Section $section = null;
    public bool $innerJoinSection = true;

    /**
     * @var Category|null the category to filter by
     */
    public ?Category $category = null;

    /**
     * @var Entry|null the parent entry to filter by
     */
    public ?Entry $parent = null;


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

        $type = Entry::instance()::getTypes()[$this->type] ?? false;

        if ($type) {
            if (isset($type['orderBy'])) {
                $this->query->orderBy($type['orderBy']);
            }

            if (isset($type['sort'])) {
                $this->setSort($type['sort']);
            }

            $this->query->andWhere([Entry::tableName() . '.[[type]]' => $this->type]);
        }

        if (static::getModule()->enableCategories) {
            $this->whereCategory();
        }

        if (static::getModule()->enableNestedEntries) {
            $this->whereEntry();
        }

        if (static::getModule()->enableSectionEntries) {
            $this->whereSection();
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    protected function whereCategory(): void
    {
        if ($this->category) {
            $this->query->whereCategory($this->category);
        }
    }

    /**
     * Limits results to the scope of `entry` or null as root unless a text search is performed. If no category
     * is defining the order, the descendant order is applied.
     */
    protected function whereEntry(): void
    {
        if ($this->searchString) {
            return;
        }

        if ($this->section && $this->innerJoinSection) {
            return;
        }

        if (!$this->category?->getEntriesOrderBy()) {
            if ($orderBy = $this->parent?->getDescendantsOrderBy()) {
                $this->query->orderBy($orderBy);
            }
        }

        $this->query->andWhere(['parent_id' => $this->parent?->id]);
    }

    protected function whereSection(): void
    {
        if ($this->section) {
            $this->query->whereSection($this->section, true, $this->innerJoinSection
                ? 'INNER JOIN'
                : 'LEFT JOIN');
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
                SectionEntry::tableName() . '.[[position]]',
                'position',
            ]);
    }
}
