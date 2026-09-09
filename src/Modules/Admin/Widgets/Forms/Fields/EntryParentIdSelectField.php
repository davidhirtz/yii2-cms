<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ParentIdSelectFieldTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;

/**
 * @template T of Entry
 * @property T $model
 */
class EntryParentIdSelectField extends SelectField
{
    use ModuleTrait;
    use ParentIdSelectFieldTrait;

    /**
     * @var T[]
     */
    private array $entries;

    #[Override]
    protected function configure(): void
    {
        $this->property ??= 'parent_id';

        $this->setItemsFromEntries($this->getEntries());
        $this->promptAttributes = ArrayHelper::remove($this->attributes, 'promptAttributes', []);

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->items) {
            // Always render row (e.g. for `yii2-cms-tenant`) but hide it if no suitable parent entries are found
            $this->rowAttributes['hidden'] = true;
        }

        return parent::renderContent();
    }

    /**
     * @param T[] $entries
     */
    protected function setItemsFromEntries(array $entries, ?int $parentId = null): void
    {
        foreach ($entries as $entry) {
            if ($entry->parent_id !== $parentId) {
                continue;
            }

            $name = Html::encode($entry->getI18nAttribute('name') ?: Lang::t('cms', 'COMMON_NO_TITLE'));
            $count = count($entry->getAncestorIds());
            $indent = ($count ? (str_repeat($this->indent, $count) . ' ') : '');

            $item = [
                'label' => $indent . $name,
                'disabled' => !$this->model->getIsNewRecord()
                    && in_array($this->model->id, [...$entry->getAncestorIds(), $entry->id], true),
            ];

            foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
                $item['data-value'][] = $this->getParentIdOptionDataValue($entry, $language);
            }

            $this->addItem($entry->id, $item);

            if ($entry->entry_count) {
                $this->setItemsFromEntries($entries, $entry->id);
            }
        }
    }

    /**
     * @return T[]
     */
    protected function getEntries(): array
    {
        $this->entries ??= array_filter($this->findEntries(), fn (Entry $entry) => $entry->hasDescendantsEnabled());
        return $this->entries;
    }

    /**
     * @return T[]
     */
    protected function findEntries(): array
    {
        return $this->getEntryQuery()
            ->whereHasDescendantsEnabled()
            ->orderBy($this->getOrderBy())
            ->indexBy('id')
            ->all();
    }

    protected function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->replaceI18nAttributes();
    }

    protected function getOrderBy(): array
    {
        return static::getModule()->defaultEntryOrderBy ?? ['position' => SORT_ASC];
    }
}
