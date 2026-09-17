<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ParentIdSelectFieldTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Yii;

/**
 * @property Entry $model
 */
class EntryParentIdSelectField extends SelectField
{
    use ModuleTrait;
    use ParentIdSelectFieldTrait;

    public ?string $property = 'parent_id';

    /**
     * @var Entry[]
     */
    private array $entries;

    #[Override]
    protected function configure(): void
    {
        $this->setItemsFromEntries($this->getEntries());

        // The parent decides the slug field's base URL, and with it every URL the rest of the form spells out.
        if ($this->items) {
            $this->reloadsForm();
        }

        parent::configure();
    }

    /**
     * The tenant select reloads the page rather than this one row, so an entry with no eligible parent simply has no
     * field instead of a hidden one waiting to be filled.
     */
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->items !== [];
    }

    /**
     * @param Entry[] $entries
     */
    protected function setItemsFromEntries(array $entries, ?int $parentId = null): void
    {
        foreach ($entries as $entry) {
            if ($entry->parent_id !== $parentId) {
                continue;
            }

            $name = Html::encode($entry->getI18nAttribute('name') ?: Yii::t('cms', 'COMMON_NO_TITLE'));
            $count = count($entry->getAncestorIds());
            $indent = ($count ? (str_repeat($this->indent, $count) . ' ') : '');

            $item = [
                'label' => $indent . $name,
                'disabled' => !$this->model->getIsNewRecord()
                    && in_array($this->model->id, [...$entry->getAncestorIds(), $entry->id], true),
            ];

            $this->addItem($entry->id, $item);

            if ($entry->entry_count) {
                $this->setItemsFromEntries($entries, $entry->id);
            }
        }
    }

    /**
     * @return Entry[]
     */
    protected function getEntries(): array
    {
        $this->entries ??= array_filter($this->findEntries(), fn (Entry $entry) => $entry->allowsDescendants());
        return $this->entries;
    }

    /**
     * @return Entry[]
     */
    protected function findEntries(): array
    {
        return $this->getEntryQuery()
            ->whereHasDescendantsEnabled()
            ->orderBy($this->getOrderBy())
            ->indexBy('id')
            ->all();
    }

    /**
     * @return EntryQuery<Entry>
     */
    protected function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->withTranslations()
            ->andWhere([Entry::tableName() . '.[[tenant_id]]' => $this->model->tenant_id]);
    }

    /**
     * @return array<string, int>
     */
    protected function getOrderBy(): array
    {
        return static::getModule()->defaultEntryOrderBy ?? ['position' => SORT_ASC];
    }
}
