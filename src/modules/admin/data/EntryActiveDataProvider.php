<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\data;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\data\ActiveDataProvider;
use Override;
use yii\data\Pagination;
use yii\data\Sort;
use yii\helpers\ArrayHelper;

/**
 * @property EntryQuery $query
 * @extends ActiveDataProvider<Entry>
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var Section|null the section to filter by
     */
    public ?Section $section = null;

    /**
     * @var bool whether to inner join the section
     */
    public bool $innerJoinSection = true;

    /**
     * @var Category|null the category to filter by
     */
    public ?Category $category = null;

    /**
     * @var Entry|null the parent entry to filter by
     */
    public ?Entry $parent = null;

    /**
     * @var string|null the search string
     */
    public ?string $searchString = null;

    /**
     * @var int|null the entry type
     */
    public ?int $type = null;

    public function __construct($config = [])
    {
        $this->query = Entry::find();
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
        if (static::getModule()->defaultEntryOrderBy) {
            $this->query->orderBy(static::getModule()->defaultEntryOrderBy);
        }

        $this->whereType();

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

    protected function whereType(): void
    {
        $typeOptions = Entry::instance()::getTypes()[$this->type] ?? false;

        if ($typeOptions) {
            if (isset($typeOptions['orderBy'])) {
                $this->query->orderBy($typeOptions['orderBy']);
            }

            if (isset($typeOptions['sort'])) {
                $this->setSort($typeOptions['sort']);
            }

            $this->query->andWhere([Entry::tableName() . '.[[type]]' => $this->type]);
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
        if ($this->searchString || $this->category) {
            return;
        }

        if ($this->section && $this->innerJoinSection) {
            return;
        }

        if ($orderBy = $this->parent?->getDescendantsOrderBy()) {
            $this->query->orderBy($orderBy);
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

    #[Override]
    protected function prepareModels(): array
    {
        if ($this->getSort() !== false) {
            $this->query->orderBy(null);
        }

        $models = parent::prepareModels();

        if ($order = $this->section?->getEntriesOrderBy()) {
            ArrayHelper::multisort($models, array_keys($order), array_values($order));
        }

        return $models;
    }

    #[Override]
    public function getPagination(): Pagination|false
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    #[Override]
    public function getSort(): Sort|false
    {
        return !$this->isOrderedByPosition() ? parent::getSort() : false;
    }

    #[Override]
    public function setSort($value): void
    {
        if (is_array($value)) {
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
