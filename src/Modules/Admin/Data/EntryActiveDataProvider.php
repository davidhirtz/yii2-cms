<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;
use yii\data\Pagination;
use yii\data\Sort;
use yii\helpers\ArrayHelper;

/**
 * @property EntryQuery<Entry> $query
 * @extends ActiveDataProvider<Entry>
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    public ?int $tenantId = null;
    public ?Category $category = null;
    public ?Entry $parent = null;
    public ?EntryRelationModelInterface $relatedModel = null;
    public ?int $type = null;
    public ?int $status = null;
    public ?string $searchString = null;
    public bool $innerJoinRelatedModel = true;

    public function __construct($config = [])
    {
        $this->query = Entry::find();
        parent::__construct($config);
    }

    #[Override]
    public function init(): void
    {
        // A record's children belong to its tenant, whichever host serves the admin.
        $this->tenantId ??= $this->parent->tenant_id ?? $this->getTenantIdFromRequest();
        parent::init();
    }

    protected function getTenantIdFromRequest(): ?int
    {
        $tenantId = (int)Application::current()->getRequest()->get('tenant');

        if ($tenantId) {
            return $tenantId;
        }

        if ($this->relatedModel instanceof Section && $this->relatedModel->entry) {
            return $this->relatedModel->entry->tenant_id;
        }

        $manager = Yii::$app->getUrlManager();
        return $manager instanceof UrlManager ? $manager->tenant?->id : null;
    }

    /**
     * @see Entry::isNewIndex()
     */
    public function createsIndex(): bool
    {
        if ($this->parent || !static::getModule()->entryIndexSlug || $this->getTotalCount() > 0) {
            return false;
        }

        $tenantId = $this->tenantId ?? TenantCollection::getDefault()?->id;

        return !Entry::find()
            ->andWhere([Entry::tableName() . '.[[tenant_id]]' => $tenantId])
            ->exists();
    }

    #[Override]
    protected function prepareQuery(): void
    {
        $this->initQuery();
        parent::prepareQuery();
    }

    protected function initQuery(): void
    {
        // The linked entries of a record are whatever it links, a block's from any tenant.
        if ($this->tenantId && !($this->relatedModel && $this->innerJoinRelatedModel)) {
            $this->query->andWhere([Entry::tableName() . '.[[tenant_id]]' => $this->tenantId]);
        }

        if (static::getModule()->defaultEntryOrderBy) {
            $this->query->orderBy(static::getModule()->defaultEntryOrderBy);
        }

        $this->whereType();

        if ($this->status !== null) {
            $this->query->andWhere([Entry::tableName() . '.[[status]]' => $this->status]);
        }

        if (static::getModule()->enableCategories) {
            $this->whereCategory();
        }

        if (static::getModule()->enableNestedEntries) {
            $this->whereEntry();
        }

        if (static::getModule()->getEntryRelationClasses()) {
            $this->whereRelatedModel();
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }

        $this->query->withPermalinks();
    }

    protected function whereType(): void
    {
        $type = Entry::instance()::findType($this->type);

        if ($type) {
            if ($orderBy = $type->getOrderBy()) {
                $this->query->orderBy($orderBy);
            }

            if ($sort = $type->getSort()) {
                $this->setSort($sort);
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

        if ($this->relatedModel && $this->innerJoinRelatedModel) {
            return;
        }

        if ($orderBy = $this->parent?->getDescendantsOrderBy()) {
            $this->query->orderBy($orderBy);
        }

        $this->query->andWhere(['parent_id' => $this->parent?->id]);
    }

    protected function whereRelatedModel(): void
    {
        if ($this->relatedModel) {
            $this->query->whereRelatedModel(
                $this->relatedModel,
                $this->innerJoinRelatedModel ? 'INNER JOIN' : 'LEFT JOIN'
            );
        }
    }

    /**
     * @return list<Entry>
     */
    #[Override]
    protected function prepareModels(): array
    {
        if ($this->getSort() !== false) {
            $this->query->orderBy(null);
        }

        $models = parent::prepareModels();

        $order = $this->relatedModel?->getEntriesOrderBy();

        if (is_array($order)) {
            ArrayHelper::multisort($models, array_keys($order), array_values($order));
        }

        return array_values($models);
    }

    #[Override]
    public function getPagination(): Pagination|false
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    #[Override]
    public function getSort(): Sort|false
    {
        $sort = parent::getSort();

        return !$this->isOrderedByPosition() && $sort instanceof Sort ? $sort : false;
    }

    /**
     * @param array<string, mixed>|Sort|bool $value
     */
    #[Override]
    public function setSort($value): void
    {
        if (is_array($value)) {
            $value['defaultOrder'] ??= $this->query->orderBy;
        }

        parent::setSort($value);
    }

    /**
     * Linked entries by their own `position` show an order the drag, which writes the link's, never changes.
     */
    public function isOrderedByPosition(): bool
    {
        $columns = $this->relatedModel && $this->innerJoinRelatedModel
            ? [EntryRelation::tableName() . '.[[position]]']
            : [EntryCategory::tableName() . '.[[position]]', EntryRelation::tableName() . '.[[position]]', 'position'];

        return isset($this->query->orderBy) && in_array(key($this->query->orderBy), $columns, true);
    }
}
